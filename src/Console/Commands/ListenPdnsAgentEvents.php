<?php

namespace NextDeveloper\DNS\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Database\Models\DnsServers;
use NextDeveloper\DNS\Database\Models\DnsZones;
use NextDeveloper\DNS\Services\DnsRecordsService;
use NextDeveloper\DNS\Services\DnsServersService;
use NextDeveloper\DNS\Services\DnsZonesService;
use NextDeveloper\Events\Services\AgentCommandsService;
use NextDeveloper\Events\Services\NatsService;
use NextDeveloper\IAM\Database\Scopes\AuthorizationScope;
use NextDeveloper\IAM\Helpers\UserHelper;

/**
 * Subscribes to agent.dns.> and reconciles DnsZones/DnsRecords status based on
 * pdns-agent command results - this is what flips a zone/record from
 * 'provisioning' to 'active'/'failed' after PowerDnsProviderService dispatches
 * a zone.create/record.create/etc command asynchronously over NATS.
 *
 * Mirrors NextDeveloper\IAAS\Console\Commands\ListenVmAgentEvents (same generic
 * agent.{type}.{uuid}.cmd/evt protocol, agent_type = 'dns' instead of 'vm').
 *
 * Usage:
 *   php artisan dns:pdns-agent-listen
 *
 * Requires NATS_ENABLED=true in .env.
 */
class ListenPdnsAgentEvents extends Command
{
    protected $signature   = 'dns:pdns-agent-listen';
    protected $description = 'Listen to pdns-agent NATS events and reconcile DNS zone/record status';

    private bool $shouldQuit = false;

    private NatsService $nats;

    public function handle(NatsService $nats): int
    {
        if (!config('events.nats.enabled', false)) {
            $this->error('NATS is not enabled. Set NATS_ENABLED=true in your .env file.');
            return 1;
        }

        $this->nats = $nats;

        $this->registerSignalHandlers();

        $nats->subscribe('agent.dns.>', function (array|string $payload, string $subject) {
            if (!is_array($payload)) {
                Log::warning('[ListenPdnsAgentEvents] Non-JSON payload received, skipping', [
                    'subject' => $subject,
                    'raw'     => is_string($payload) ? substr($payload, 0, 200) : gettype($payload),
                ]);
                return;
            }

            match ($payload['type'] ?? '') {
                'heartbeat' => $this->handleHeartbeat($payload),
                'result'    => $this->handleResult($payload),
                default     => Log::warning('[ListenPdnsAgentEvents] Unhandled message type', [
                    'type'       => $payload['type'] ?? '(missing)',
                    'agent_uuid' => $payload['agent_uuid'] ?? null,
                    'subject'    => $subject,
                ]),
            };
        });

        $this->info('Subscribed to agent.dns.> — reconciling zone/record status from pdns-agent results. Press Ctrl+C to stop.');

        while (!$this->shouldQuit) {
            try {
                $nats->process(0.1);
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'Interrupted system call')) {
                    break;
                }
                throw $e;
            }
            pcntl_signal_dispatch();
        }

        $this->info('Listener stopped.');
        return 0;
    }

    private function handleHeartbeat(array $payload): void
    {
        $agentUuid = $payload['agent_uuid'] ?? null;

        if (!$agentUuid) {
            Log::warning('[ListenPdnsAgentEvents] Heartbeat missing agent_uuid');
            return;
        }

        $server = DnsServers::withoutGlobalScope(AuthorizationScope::class)
            ->where('agent_uuid', $agentUuid)
            ->first();

        if (!$server) {
            Log::warning('[ListenPdnsAgentEvents] No DnsServers row for agent', ['agent_uuid' => $agentUuid]);
            return;
        }

        UserHelper::runAsAdmin(fn () => DnsServersService::updateRaw([
            'id'                 => $server->uuid,
            'agent_status'       => 'online',
            'agent_last_seen_at' => now(),
        ]));
    }

    private function handleResult(array $payload): void
    {
        $result      = $payload['payload'] ?? [];
        $commandUuid = $result['command_id'] ?? null;

        if (!$commandUuid) {
            Log::warning('[ListenPdnsAgentEvents] Result message missing command_id', ['payload' => $payload]);
            return;
        }

        $command = AgentCommandsService::getByRef($commandUuid);

        if (!$command) {
            Log::warning('[ListenPdnsAgentEvents] Unknown command_id in result', ['command_id' => $commandUuid]);
            return;
        }

        $status       = $result['status']  ?? 'completed';
        $errorMessage = $result['message'] ?? null;

        AgentCommandsService::update($command->id, [
            'status'       => $status,
            'result'       => $result['output'] ?? [],
            'error'        => $errorMessage,
            'completed_at' => now(),
        ]);

        $this->reconcileStatus($command, $status, $errorMessage);

        Log::info('[ListenPdnsAgentEvents] Command result received', [
            'command_id' => $commandUuid,
            'operation'  => $command->operation,
            'status'     => $status,
        ]);
    }

    /**
     * Flips the zone/record's status based on the command outcome. Delete
     * operations are logged only - by the time a delete result arrives, the
     * local row has already been removed (see DnsZonesService::delete()/
     * DnsRecordsService::delete(), which delete locally right after dispatching
     * the provider call) - reconciling a failed delete back would mean restoring
     * a soft-deleted row, deliberately left as a manual follow-up rather than
     * automated here.
     */
    private function reconcileStatus($command, string $status, ?string $errorMessage): void
    {
        $params    = $command->params ?? [];
        $newStatus = $status === 'completed' ? 'active' : 'failed';

        UserHelper::runAsAdmin(function () use ($command, $params, $newStatus, $errorMessage) {
            if (!empty($params['zone_uuid']) && in_array($command->operation, ['zone.create', 'zone.sync'], true)) {
                $zone = DnsZones::withoutGlobalScope(AuthorizationScope::class)
                    ->where('uuid', $params['zone_uuid'])
                    ->first();

                if ($zone) {
                    DnsZonesService::updateRaw([
                        'id'         => $zone->uuid,
                        'status'     => $newStatus,
                        'last_error' => $newStatus === 'failed' ? $errorMessage : null,
                    ]);
                }
            }

            if (!empty($params['record_uuid']) && in_array($command->operation, ['record.create', 'record.update'], true)) {
                $record = DnsRecords::withoutGlobalScope(AuthorizationScope::class)
                    ->where('uuid', $params['record_uuid'])
                    ->first();

                if ($record) {
                    DnsRecordsService::updateRaw([
                        'id'         => $record->uuid,
                        'status'     => $newStatus,
                        'last_error' => $newStatus === 'failed' ? $errorMessage : null,
                    ]);
                }
            }
        });
    }

    private function registerSignalHandlers(): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $this->shouldQuit = true);
        pcntl_signal(SIGINT,  fn () => $this->shouldQuit = true);
    }
}

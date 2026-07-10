<?php

namespace NextDeveloper\DNS\Services\DnsProviders\PowerDns;

use NextDeveloper\DNS\Contracts\DnsProviderInterface;
use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Database\Models\DnsZones;
use NextDeveloper\Events\Services\AgentCommandsService;

/**
 * Talks to a self-hosted PowerDNS instance through its pdns-agent (a NATS-connected
 * Go agent forked from vm.agent). Never touches PowerDNS's backend DB directly -
 * the agent calls PowerDNS's own local REST API so PowerDNS keeps owning its own
 * consistency/AXFR/notify mechanics.
 *
 * Command dispatch is asynchronous: createZone()/deleteZone()/create|update|deleteRecord()
 * publish the command and return immediately. The zone/record's status flips from
 * 'provisioning' to 'active'/'failed' when the agent's reply comes back on
 * agent.dns.{server_uuid}.evt - see Console\Commands\ListenPdnsAgentEvents.
 */
class PowerDnsProviderService implements DnsProviderInterface
{
    public function createZone(DnsZones $zone): void
    {
        $server = $zone->dnsServer;

        AgentCommandsService::dispatch(
            agentType: 'dns',
            agentUuid: $server->agent_uuid,
            operation: 'zone.create',
            params: [
                'zone_uuid'   => $zone->uuid,
                'name'        => $zone->name,
                'kind'        => 'Native',
                'nameservers' => $zone->soa_primary_ns ? [$zone->soa_primary_ns] : [],
            ],
            timeoutS: config('dns.agent_command_timeout_s', 60)
        );
    }

    public function deleteZone(DnsZones $zone): void
    {
        $server = $zone->dnsServer;

        AgentCommandsService::dispatch(
            agentType: 'dns',
            agentUuid: $server->agent_uuid,
            operation: 'zone.delete',
            params: [
                'zone_uuid' => $zone->uuid,
                'name'      => $zone->name,
            ],
            timeoutS: config('dns.agent_command_timeout_s', 60)
        );
    }

    /**
     * Sends the full set of records we believe should exist so the agent can
     * reconcile PowerDNS's actual state against it - recovers from a failed
     * createZone or from manual out-of-band changes.
     */
    public function syncZone(DnsZones $zone): void
    {
        $server = $zone->dnsServer;

        AgentCommandsService::dispatch(
            agentType: 'dns',
            agentUuid: $server->agent_uuid,
            operation: 'zone.sync',
            params: [
                'zone_uuid' => $zone->uuid,
                'name'      => $zone->name,
                'records'   => $this->listRecords($zone),
            ],
            timeoutS: config('dns.agent_command_timeout_s', 60)
        );
    }

    public function createRecord(DnsRecords $record): void
    {
        $zone = $record->zone;

        AgentCommandsService::dispatch(
            agentType: 'dns',
            agentUuid: $zone->dnsServer->agent_uuid,
            operation: 'record.create',
            params: $this->recordParams($zone, $record),
            timeoutS: config('dns.agent_command_timeout_s', 60)
        );
    }

    public function updateRecord(DnsRecords $record): void
    {
        $zone = $record->zone;

        AgentCommandsService::dispatch(
            agentType: 'dns',
            agentUuid: $zone->dnsServer->agent_uuid,
            operation: 'record.update',
            params: $this->recordParams($zone, $record),
            timeoutS: config('dns.agent_command_timeout_s', 60)
        );
    }

    public function deleteRecord(DnsRecords $record): void
    {
        $zone = $record->zone;

        AgentCommandsService::dispatch(
            agentType: 'dns',
            agentUuid: $zone->dnsServer->agent_uuid,
            operation: 'record.delete',
            params: $this->recordParams($zone, $record),
            timeoutS: config('dns.agent_command_timeout_s', 60)
        );
    }

    /**
     * The platform's own DnsRecords rows are the source of truth for what should
     * exist - PowerDNS is a write target, not something we round-trip to for reads.
     */
    public function listRecords(DnsZones $zone): array
    {
        return $zone->records()->get()->map(fn (DnsRecords $record) => [
            'type'     => $record->type,
            'name'     => $record->name,
            'content'  => $record->content,
            'ttl'      => $record->ttl,
            'priority' => $record->priority,
        ])->all();
    }

    private function recordParams(DnsZones $zone, DnsRecords $record): array
    {
        return [
            'zone_uuid'   => $zone->uuid,
            'zone_name'   => $zone->name,
            'record_uuid' => $record->uuid,
            'type'        => $record->type,
            'name'        => $record->name,
            'content'     => $record->content,
            'ttl'         => $record->ttl,
            'priority'    => $record->priority,
        ];
    }
}

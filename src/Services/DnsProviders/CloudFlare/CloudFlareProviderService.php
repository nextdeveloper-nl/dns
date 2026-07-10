<?php

namespace NextDeveloper\DNS\Services\DnsProviders\CloudFlare;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\DNS\Contracts\DnsProviderInterface;
use NextDeveloper\DNS\Contracts\DnssecCapableInterface;
use NextDeveloper\DNS\Contracts\ProxyCapableInterface;
use NextDeveloper\DNS\Database\Models\DnsProviderCredentials;
use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Database\Models\DnsZones;

/**
 * Talks directly to CloudFlare's REST API v4 using the customer's own API token
 * (DnsZones::dnsProviderCredential) - no agent, no NATS, since this is an external
 * SaaS call rather than PlusClouds-owned infrastructure. Synchronous: zone/record
 * status is updated in the same request, unlike the PowerDNS path.
 */
class CloudFlareProviderService implements DnsProviderInterface, DnssecCapableInterface, ProxyCapableInterface
{
    public function createZone(DnsZones $zone): void
    {
        $credential = $zone->dnsProviderCredential;

        $response = $this->client($credential)->post('/zones', [
            'name'    => $zone->name,
            'account' => ['id' => $credential->cloudflare_account_id],
        ]);

        $this->assertSuccessful($response, $zone, 'create zone');

        $result = $response->json('result');

        $zone->update([
            'external_id'    => $result['id'],
            'status'         => 'active',
            'soa_primary_ns' => $result['name_servers'][0] ?? null,
        ]);
    }

    public function deleteZone(DnsZones $zone): void
    {
        if (!$zone->external_id) {
            return;
        }

        $response = $this->client($zone->dnsProviderCredential)
            ->delete("/zones/{$zone->external_id}");

        $this->assertSuccessful($response, $zone, 'delete zone');
    }

    public function syncZone(DnsZones $zone): void
    {
        // CloudFlare is the provider's own source of truth once a zone exists there -
        // nothing to reconcile from our side beyond what create/update/delete already do.
    }

    public function createRecord(DnsRecords $record): void
    {
        $zone = $record->zone;

        $response = $this->client($zone->dnsProviderCredential)
            ->post("/zones/{$zone->external_id}/dns_records", $this->recordPayload($record));

        $this->assertSuccessful($response, $record, 'create record');

        $record->update([
            'external_id' => $response->json('result.id'),
            'status'      => 'active',
        ]);
    }

    public function updateRecord(DnsRecords $record): void
    {
        $zone = $record->zone;

        $response = $this->client($zone->dnsProviderCredential)
            ->patch("/zones/{$zone->external_id}/dns_records/{$record->external_id}", $this->recordPayload($record));

        $this->assertSuccessful($response, $record, 'update record');

        $record->update(['status' => 'active']);
    }

    public function deleteRecord(DnsRecords $record): void
    {
        if (!$record->external_id) {
            return;
        }

        $zone = $record->zone;

        $response = $this->client($zone->dnsProviderCredential)
            ->delete("/zones/{$zone->external_id}/dns_records/{$record->external_id}");

        $this->assertSuccessful($response, $record, 'delete record');
    }

    public function listRecords(DnsZones $zone): array
    {
        $response = $this->client($zone->dnsProviderCredential)
            ->get("/zones/{$zone->external_id}/dns_records");

        $this->assertSuccessful($response, $zone, 'list records');

        return collect($response->json('result', []))->map(fn (array $r) => [
            'type'     => $r['type'],
            'name'     => $r['name'],
            'content'  => $r['content'],
            'ttl'      => $r['ttl'],
            'priority' => $r['priority'] ?? null,
        ])->all();
    }

    public function enableDnssec(DnsZones $zone): array
    {
        $response = $this->client($zone->dnsProviderCredential)
            ->patch("/zones/{$zone->external_id}/dnssec", ['status' => 'active']);

        $this->assertSuccessful($response, $zone, 'enable DNSSEC');

        $ds = $response->json('result.ds', []);

        $zone->update([
            'is_dnssec_enabled' => true,
            'dnssec_ds_records' => $ds,
        ]);

        return $ds;
    }

    public function disableDnssec(DnsZones $zone): void
    {
        $response = $this->client($zone->dnsProviderCredential)
            ->patch("/zones/{$zone->external_id}/dnssec", ['status' => 'disabled']);

        $this->assertSuccessful($response, $zone, 'disable DNSSEC');

        $zone->update([
            'is_dnssec_enabled' => false,
            'dnssec_ds_records' => [],
        ]);
    }

    public function setProxied(DnsRecords $record, bool $proxied): void
    {
        $zone = $record->zone;

        $response = $this->client($zone->dnsProviderCredential)
            ->patch("/zones/{$zone->external_id}/dns_records/{$record->external_id}", ['proxied' => $proxied]);

        $this->assertSuccessful($response, $record, 'toggle proxy');

        $record->update(['is_proxied' => $proxied]);
    }

    private function recordPayload(DnsRecords $record): array
    {
        return [
            'type'     => $record->type,
            'name'     => $record->name,
            'content'  => $record->content,
            'ttl'      => $record->ttl,
            'priority' => $record->priority,
            'proxied'  => (bool) $record->is_proxied,
        ];
    }

    private function client(DnsProviderCredentials $credential)
    {
        // api_token_enc is transparently decrypted by the model's `encrypted` cast -
        // this is the customer's real CloudFlare token, never logged.
        return Http::withToken($credential->api_token_enc)
            ->baseUrl(config('dns.cloudflare.api_base'))
            ->acceptJson();
    }

    private function assertSuccessful(Response $response, $model, string $action): void
    {
        if ($response->successful() && $response->json('success') === true) {
            return;
        }

        $errors = $response->json('errors', []);
        $message = $errors[0]['message'] ?? $response->body();

        $model->update([
            'status'     => 'failed',
            'last_error' => "CloudFlare {$action} failed: {$message}",
        ]);

        throw new NotAllowedException("CloudFlare {$action} failed: {$message}");
    }
}

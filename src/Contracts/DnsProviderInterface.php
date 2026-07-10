<?php

namespace NextDeveloper\DNS\Contracts;

use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Database\Models\DnsZones;

/**
 * Every DNS backend (PowerDNS, CloudFlare, ...) implements this contract.
 * DnsProviderManager resolves the correct implementation per zone via
 * DnsZones::provider and dispatches through it - callers never talk to
 * PowerDnsProviderService/CloudFlareProviderService directly.
 */
interface DnsProviderInterface
{
    public function createZone(DnsZones $zone): void;

    public function deleteZone(DnsZones $zone): void;

    /**
     * Reconciles the zone's records on the provider with what's stored locally -
     * used to recover from a failed/partial createZone or after manual changes.
     */
    public function syncZone(DnsZones $zone): void;

    public function createRecord(DnsRecords $record): void;

    public function updateRecord(DnsRecords $record): void;

    public function deleteRecord(DnsRecords $record): void;

    /**
     * Returns the provider's current view of a zone's records, in the same
     * shape as DnsRecords columns (type, name, content, ttl, priority) - used
     * by syncZone() and for drift detection.
     */
    public function listRecords(DnsZones $zone): array;
}

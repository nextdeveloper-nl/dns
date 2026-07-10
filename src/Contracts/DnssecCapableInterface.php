<?php

namespace NextDeveloper\DNS\Contracts;

use NextDeveloper\DNS\Database\Models\DnsZones;

/**
 * Optional capability - only CloudFlareProviderService implements this today.
 * PowerDnsProviderService does not, so DnsProviderManager checks
 * `instanceof DnssecCapableInterface` before calling these (mirrors
 * SnapshotCapableInterface/CloneCapableInterface in IAAS's HypervisorsV2).
 */
interface DnssecCapableInterface
{
    /**
     * Enables DNSSEC on the zone and returns the DS records the registrar
     * needs to publish (also stored on DnsZones::dnssec_ds_records).
     */
    public function enableDnssec(DnsZones $zone): array;

    public function disableDnssec(DnsZones $zone): void;
}

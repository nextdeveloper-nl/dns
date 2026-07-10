<?php

namespace NextDeveloper\DNS\Services;

use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\DNS\Contracts\DnsProviderInterface;
use NextDeveloper\DNS\Contracts\DnssecCapableInterface;
use NextDeveloper\DNS\Contracts\ProxyCapableInterface;
use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Database\Models\DnsZones;
use NextDeveloper\DNS\Exceptions\AdapterNotFoundException;

/**
 * Resolves the DnsProviderInterface adapter for a zone (via DnsZones::provider)
 * and dispatches every zone/record operation through it. Callers (Actions,
 * DnsZonesService/DnsRecordsService) should only ever talk to this class, never
 * to PowerDnsProviderService/CloudFlareProviderService directly - mirrors
 * IAAS's HypervisorsV2\VirtualMachineManager.
 */
class DnsProviderManager
{
    /**
     * @var array<string, string> provider name => adapter class
     */
    private array $adapters = [];

    public function registerAdapter(string $provider, string $adapterClass): void
    {
        $this->adapters[$provider] = $adapterClass;
    }

    public function getAdapter(DnsZones $zone): DnsProviderInterface
    {
        if (!isset($this->adapters[$zone->provider])) {
            throw new AdapterNotFoundException("No DNS adapter registered for provider: {$zone->provider}");
        }

        return app($this->adapters[$zone->provider]);
    }

    public function createZone(DnsZones $zone): void
    {
        $this->getAdapter($zone)->createZone($zone);
    }

    public function deleteZone(DnsZones $zone): void
    {
        $this->getAdapter($zone)->deleteZone($zone);
    }

    public function syncZone(DnsZones $zone): void
    {
        $this->getAdapter($zone)->syncZone($zone);
    }

    public function listRecords(DnsZones $zone): array
    {
        return $this->getAdapter($zone)->listRecords($zone);
    }

    public function createRecord(DnsRecords $record): void
    {
        $this->getAdapter($record->zone)->createRecord($record);
    }

    public function updateRecord(DnsRecords $record): void
    {
        $this->getAdapter($record->zone)->updateRecord($record);
    }

    public function deleteRecord(DnsRecords $record): void
    {
        $this->getAdapter($record->zone)->deleteRecord($record);
    }

    /**
     * @return array DS records to publish with the registrar
     */
    public function enableDnssec(DnsZones $zone): array
    {
        $adapter = $this->getAdapter($zone);

        if (!$adapter instanceof DnssecCapableInterface) {
            throw new NotAllowedException("Provider [{$zone->provider}] does not support DNSSEC");
        }

        return $adapter->enableDnssec($zone);
    }

    public function disableDnssec(DnsZones $zone): void
    {
        $adapter = $this->getAdapter($zone);

        if (!$adapter instanceof DnssecCapableInterface) {
            throw new NotAllowedException("Provider [{$zone->provider}] does not support DNSSEC");
        }

        $adapter->disableDnssec($zone);
    }

    public function setProxied(DnsRecords $record, bool $proxied): void
    {
        $adapter = $this->getAdapter($record->zone);

        if (!$adapter instanceof ProxyCapableInterface) {
            throw new NotAllowedException("Provider [{$record->zone->provider}] does not support proxying");
        }

        $adapter->setProxied($record, $proxied);
    }
}

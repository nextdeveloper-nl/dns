<?php

namespace NextDeveloper\DNS\Services;

use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\DNS\Database\Models\DnsZones;
use NextDeveloper\DNS\Services\AbstractServices\AbstractDnsZonesService;

/**
 * This class is responsible from managing the data for DnsZones
 *
 * Class DnsZonesService.
 *
 * @package NextDeveloper\DNS\Database\Models
 */
class DnsZonesService extends AbstractDnsZonesService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Create the zone record, then dispatch its creation to the resolved provider
     * adapter. PowerDNS zones stay 'provisioning' until ListenPdnsAgentEvents flips
     * them to active/failed; CloudFlare zones are updated synchronously by
     * CloudFlareProviderService::createZone() within the same request.
     */
    public static function create(array $data)
    {
        $provider = $data['provider'] ?? null;

        if ($provider === 'powerdns' && empty($data['dns_server_id'])) {
            throw new NotAllowedException('A PowerDNS zone requires dns_server_id.');
        }

        if ($provider === 'cloudflare' && empty($data['dns_provider_credential_id'])) {
            throw new NotAllowedException('A CloudFlare zone requires dns_provider_credential_id.');
        }

        $data['status'] = $data['status'] ?? 'provisioning';

        $model = parent::create($data);

        app(DnsProviderManager::class)->createZone($model);

        return $model->fresh();
    }

    /**
     * Deletes on the provider first, then removes the local record - if the
     * provider call throws, the zone stays around for a retry instead of the
     * platform losing track of something that's still live.
     */
    public static function delete($id)
    {
        $model = DnsZones::where('uuid', $id)->first();

        if (!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to delete. ' .
                'Maybe you dont have the permission to delete this object?'
            );
        }

        app(DnsProviderManager::class)->deleteZone($model);

        return parent::delete($id);
    }

    /**
     * Reconciles the zone's records against the provider - recovers from a
     * failed/partial createZone or manual out-of-band changes.
     */
    public static function sync(string $id): DnsZones
    {
        $model = DnsZones::where('uuid', $id)->firstOrFail();

        app(DnsProviderManager::class)->syncZone($model);

        return $model->fresh();
    }
}

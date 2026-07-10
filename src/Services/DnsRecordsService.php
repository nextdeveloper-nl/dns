<?php

namespace NextDeveloper\DNS\Services;

use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Services\AbstractServices\AbstractDnsRecordsService;

/**
 * This class is responsible from managing the data for DnsRecords
 *
 * Class DnsRecordsService.
 *
 * @package NextDeveloper\DNS\Database\Models
 */
class DnsRecordsService extends AbstractDnsRecordsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Create the record, then dispatch its creation to the zone's provider adapter.
     */
    public static function create(array $data)
    {
        $data['status'] = $data['status'] ?? 'provisioning';

        $model = parent::create($data);

        app(DnsProviderManager::class)->createRecord($model);

        return $model->fresh();
    }

    /**
     * Update the record, then push the change to the zone's provider adapter.
     */
    public static function update($id, array $data)
    {
        $model = parent::update($id, $data);

        app(DnsProviderManager::class)->updateRecord($model);

        return $model->fresh();
    }

    /**
     * Deletes on the provider first, then removes the local record - if the
     * provider call throws, the record stays around for a retry instead of the
     * platform losing track of something that's still live.
     */
    public static function delete($id)
    {
        $model = DnsRecords::where('uuid', $id)->first();

        if (!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to delete. ' .
                'Maybe you dont have the permission to delete this object?'
            );
        }

        app(DnsProviderManager::class)->deleteRecord($model);

        return parent::delete($id);
    }
}

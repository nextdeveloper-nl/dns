<?php

namespace NextDeveloper\DNS\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\DNS\Database\Models\DnsZones;
use NextDeveloper\DNS\Http\Transformers\AbstractTransformers\AbstractDnsZonesTransformer;

/**
 * Class DnsZonesTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\DNS\Http\Transformers
 */
class DnsZonesTransformer extends AbstractDnsZonesTransformer
{
    /**
     * @param DnsZones $model
     *
     * @return array
     */
    public function transform(DnsZones $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('DnsZones', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('DnsZones', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        return $transformed;
    }
}

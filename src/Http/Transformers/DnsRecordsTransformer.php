<?php

namespace NextDeveloper\DNS\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Http\Transformers\AbstractTransformers\AbstractDnsRecordsTransformer;

/**
 * Class DnsRecordsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\DNS\Http\Transformers
 */
class DnsRecordsTransformer extends AbstractDnsRecordsTransformer
{
    /**
     * @param DnsRecords $model
     *
     * @return array
     */
    public function transform(DnsRecords $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('DnsRecords', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('DnsRecords', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        return $transformed;
    }
}

<?php

namespace NextDeveloper\DNS\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\DNS\Database\Models\DnsServers;
use NextDeveloper\DNS\Http\Transformers\AbstractTransformers\AbstractDnsServersTransformer;

/**
 * Class DnsServersTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\DNS\Http\Transformers
 */
class DnsServersTransformer extends AbstractDnsServersTransformer
{
    /**
     * @param DnsServers $model
     *
     * @return array
     */
    public function transform(DnsServers $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('DnsServers', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('DnsServers', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        return $transformed;
    }
}

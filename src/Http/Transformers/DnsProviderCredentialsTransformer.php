<?php

namespace NextDeveloper\DNS\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\DNS\Database\Models\DnsProviderCredentials;
use NextDeveloper\DNS\Http\Transformers\AbstractTransformers\AbstractDnsProviderCredentialsTransformer;

/**
 * Class DnsProviderCredentialsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\DNS\Http\Transformers
 */
class DnsProviderCredentialsTransformer extends AbstractDnsProviderCredentialsTransformer
{
    /**
     * @param DnsProviderCredentials $model
     *
     * @return array
     */
    public function transform(DnsProviderCredentials $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('DnsProviderCredentials', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('DnsProviderCredentials', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        return $transformed;
    }
}

<?php

namespace NextDeveloper\DNS\Http\Transformers\AbstractTransformers;

use NextDeveloper\Commons\Database\Models\AvailableActions;
use NextDeveloper\Commons\Http\Transformers\AvailableActionsTransformer;
use NextDeveloper\Commons\Database\Models\States;
use NextDeveloper\Commons\Http\Transformers\StatesTransformer;
use NextDeveloper\DNS\Database\Models\DnsServers;
use NextDeveloper\DNS\Database\Models\DnsProviderCredentials;
use NextDeveloper\DNS\Database\Models\DnsZones;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\IAM\Database\Scopes\AuthorizationScope;

/**
 * Class AbstractDnsZonesTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\DNS\Http\Transformers
 */
class AbstractDnsZonesTransformer extends AbstractTransformer
{
    /**
     * @var array
     */
    protected array $availableIncludes = [
        'states',
        'actions',
    ];

    /**
     * @param DnsZones $model
     *
     * @return array
     */
    public function transform(DnsZones $model)
    {
        $dnsServerId = DnsServers::where('id', $model->dns_server_id)->first();
        $dnsProviderCredentialId = DnsProviderCredentials::where('id', $model->dns_provider_credential_id)->first();
        $iamAccountId = \NextDeveloper\IAM\Database\Models\Accounts::where('id', $model->iam_account_id)->first();
        $iamUserId = \NextDeveloper\IAM\Database\Models\Users::where('id', $model->iam_user_id)->first();

        return $this->buildPayload(
            [
            'id'  =>  $model->uuid,
            'iam_account_id'  =>  $iamAccountId ? $iamAccountId->uuid : null,
            'iam_user_id'  =>  $iamUserId ? $iamUserId->uuid : null,
            'name'  =>  $model->name,
            'provider'  =>  $model->provider,
            'dns_server_id'  =>  $dnsServerId ? $dnsServerId->uuid : null,
            'dns_provider_credential_id'  =>  $dnsProviderCredentialId ? $dnsProviderCredentialId->uuid : null,
            'soa_primary_ns'  =>  $model->soa_primary_ns,
            'soa_admin_email'  =>  $model->soa_admin_email,
            'soa_serial'  =>  $model->soa_serial,
            'soa_refresh'  =>  $model->soa_refresh,
            'soa_retry'  =>  $model->soa_retry,
            'soa_expire'  =>  $model->soa_expire,
            'soa_ttl'  =>  $model->soa_ttl,
            'is_dnssec_enabled'  =>  $model->is_dnssec_enabled,
            'dnssec_ds_records'  =>  $model->dnssec_ds_records,
            'status'  =>  $model->status,
            'last_error'  =>  $model->last_error,
            'tags'  =>  $model->tags,
            'created_at'  =>  $model->created_at,
            'updated_at'  =>  $model->updated_at,
            'deleted_at'  =>  $model->deleted_at,
            ]
        );
    }

    public function includeStates(DnsZones $model)
    {
        $states = States::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($states, new StatesTransformer());
    }

    public function includeActions(DnsZones $model)
    {
        $input = get_class($model);
        $input = str_replace('\\Database\\Models', '', $input);

        $actions = AvailableActions::withoutGlobalScope(AuthorizationScope::class)
            ->where('input', $input)
            ->get();

        return $this->collection($actions, new AvailableActionsTransformer());
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

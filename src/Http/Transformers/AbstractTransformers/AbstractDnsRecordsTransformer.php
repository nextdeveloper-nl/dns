<?php

namespace NextDeveloper\DNS\Http\Transformers\AbstractTransformers;

use NextDeveloper\Commons\Database\Models\AvailableActions;
use NextDeveloper\Commons\Http\Transformers\AvailableActionsTransformer;
use NextDeveloper\Commons\Database\Models\States;
use NextDeveloper\Commons\Http\Transformers\StatesTransformer;
use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Database\Models\DnsZones;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\IAM\Database\Scopes\AuthorizationScope;

/**
 * Class AbstractDnsRecordsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\DNS\Http\Transformers
 */
class AbstractDnsRecordsTransformer extends AbstractTransformer
{
    /**
     * @var array
     */
    protected array $availableIncludes = [
        'states',
        'actions',
    ];

    /**
     * @param DnsRecords $model
     *
     * @return array
     */
    public function transform(DnsRecords $model)
    {
        $dnsZoneId = DnsZones::where('id', $model->dns_zone_id)->first();
        $iamAccountId = \NextDeveloper\IAM\Database\Models\Accounts::where('id', $model->iam_account_id)->first();
        $iamUserId = \NextDeveloper\IAM\Database\Models\Users::where('id', $model->iam_user_id)->first();

        return $this->buildPayload(
            [
            'id'  =>  $model->uuid,
            'dns_zone_id'  =>  $dnsZoneId ? $dnsZoneId->uuid : null,
            'iam_account_id'  =>  $iamAccountId ? $iamAccountId->uuid : null,
            'iam_user_id'  =>  $iamUserId ? $iamUserId->uuid : null,
            'type'  =>  $model->type,
            'name'  =>  $model->name,
            'content'  =>  $model->content,
            'ttl'  =>  $model->ttl,
            'priority'  =>  $model->priority,
            'is_proxied'  =>  $model->is_proxied,
            'status'  =>  $model->status,
            'last_error'  =>  $model->last_error,
            'tags'  =>  $model->tags,
            'created_at'  =>  $model->created_at,
            'updated_at'  =>  $model->updated_at,
            'deleted_at'  =>  $model->deleted_at,
            ]
        );
    }

    public function includeStates(DnsRecords $model)
    {
        $states = States::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($states, new StatesTransformer());
    }

    public function includeActions(DnsRecords $model)
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

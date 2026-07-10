<?php

namespace NextDeveloper\DNS\Http\Transformers\AbstractTransformers;

use NextDeveloper\Commons\Database\Models\AvailableActions;
use NextDeveloper\Commons\Http\Transformers\AvailableActionsTransformer;
use NextDeveloper\Commons\Database\Models\States;
use NextDeveloper\Commons\Http\Transformers\StatesTransformer;
use NextDeveloper\DNS\Database\Models\DnsProviderCredentials;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\IAM\Database\Scopes\AuthorizationScope;

/**
 * Class AbstractDnsProviderCredentialsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\DNS\Http\Transformers
 */
class AbstractDnsProviderCredentialsTransformer extends AbstractTransformer
{
    /**
     * @var array
     */
    protected array $availableIncludes = [
        'states',
        'actions',
    ];

    /**
     * api_token_enc is deliberately never included here - the real CloudFlare
     * token must never be exposed back through the API.
     *
     * @param DnsProviderCredentials $model
     *
     * @return array
     */
    public function transform(DnsProviderCredentials $model)
    {
        $iamAccountId = \NextDeveloper\IAM\Database\Models\Accounts::where('id', $model->iam_account_id)->first();
        $iamUserId = \NextDeveloper\IAM\Database\Models\Users::where('id', $model->iam_user_id)->first();

        return $this->buildPayload(
            [
            'id'  =>  $model->uuid,
            'iam_account_id'  =>  $iamAccountId ? $iamAccountId->uuid : null,
            'iam_user_id'  =>  $iamUserId ? $iamUserId->uuid : null,
            'provider'  =>  $model->provider,
            'cloudflare_account_id'  =>  $model->cloudflare_account_id,
            'name'  =>  $model->name,
            'status'  =>  $model->status,
            'last_verified_at'  =>  $model->last_verified_at,
            'last_verify_error'  =>  $model->last_verify_error,
            'tags'  =>  $model->tags,
            'created_at'  =>  $model->created_at,
            'updated_at'  =>  $model->updated_at,
            'deleted_at'  =>  $model->deleted_at,
            ]
        );
    }

    public function includeStates(DnsProviderCredentials $model)
    {
        $states = States::where('object_type', get_class($model))
            ->where('object_id', $model->id)
            ->get();

        return $this->collection($states, new StatesTransformer());
    }

    public function includeActions(DnsProviderCredentials $model)
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

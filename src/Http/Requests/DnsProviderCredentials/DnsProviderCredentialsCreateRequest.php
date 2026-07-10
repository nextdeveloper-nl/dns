<?php

namespace NextDeveloper\DNS\Http\Requests\DnsProviderCredentials;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DnsProviderCredentialsCreateRequest extends AbstractFormRequest
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    public function rules()
    {
        return [
            'provider'               => 'required|string|in:cloudflare',
            // Accepted as api_token, remapped to api_token_enc in DnsProviderCredentialsService::create()
            'api_token'              => 'required|string',
            'cloudflare_account_id'  => 'required_if:provider,cloudflare|string',
            'name'                   => 'nullable|string',
            'tags'                   => 'nullable',
        ];
    }
}

<?php

namespace NextDeveloper\DNS\Http\Requests\DnsProviderCredentials;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DnsProviderCredentialsUpdateRequest extends AbstractFormRequest
{
    public function rules()
    {
        return [
            'api_token'              => 'nullable|string',
            'cloudflare_account_id'  => 'nullable|string',
            'name'                   => 'nullable|string',
            'tags'                   => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

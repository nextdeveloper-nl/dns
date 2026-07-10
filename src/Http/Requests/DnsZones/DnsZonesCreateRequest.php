<?php

namespace NextDeveloper\DNS\Http\Requests\DnsZones;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DnsZonesCreateRequest extends AbstractFormRequest
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    public function rules()
    {
        return [
            'name'                       => 'required|string',
            'provider'                   => 'required|string|in:powerdns,cloudflare',
            // Exactly one of these is required depending on provider - enforced in DnsZonesService::create()
            'dns_server_id'              => 'nullable|exists:dns_servers,uuid|uuid',
            'dns_provider_credential_id' => 'nullable|exists:dns_provider_credentials,uuid|uuid',
            'soa_admin_email'            => 'nullable|email',
            'soa_ttl'                    => 'nullable|integer|min:60',
            'tags'                       => 'nullable',
        ];
    }
}

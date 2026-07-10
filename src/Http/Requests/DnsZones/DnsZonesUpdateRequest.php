<?php

namespace NextDeveloper\DNS\Http\Requests\DnsZones;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DnsZonesUpdateRequest extends AbstractFormRequest
{
    public function rules()
    {
        return [
            // provider/dns_server_id/dns_provider_credential_id are immutable after creation
            'soa_admin_email' => 'nullable|email',
            'soa_ttl'         => 'nullable|integer|min:60',
            'tags'            => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

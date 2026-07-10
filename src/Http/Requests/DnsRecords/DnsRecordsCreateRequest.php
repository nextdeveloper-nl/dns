<?php

namespace NextDeveloper\DNS\Http\Requests\DnsRecords;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DnsRecordsCreateRequest extends AbstractFormRequest
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    public function rules()
    {
        return [
            'dns_zone_id' => 'required|exists:dns_zones,uuid|uuid',
            'type'        => 'required|string|in:A,AAAA,CNAME,MX,TXT,SRV,NS,CAA',
            'name'        => 'required|string',
            'content'     => 'required|string',
            'ttl'         => 'nullable|integer|min:60',
            'priority'    => 'nullable|integer|required_if:type,MX,SRV',
            'is_proxied'  => 'nullable|boolean',
            'tags'        => 'nullable',
        ];
    }
}

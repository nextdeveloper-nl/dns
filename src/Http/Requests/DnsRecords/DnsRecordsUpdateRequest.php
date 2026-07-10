<?php

namespace NextDeveloper\DNS\Http\Requests\DnsRecords;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DnsRecordsUpdateRequest extends AbstractFormRequest
{
    public function rules()
    {
        return [
            // dns_zone_id and type are immutable after creation
            'name'       => 'nullable|string',
            'content'    => 'nullable|string',
            'ttl'        => 'nullable|integer|min:60',
            'priority'   => 'nullable|integer',
            'is_proxied' => 'nullable|boolean',
            'tags'       => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

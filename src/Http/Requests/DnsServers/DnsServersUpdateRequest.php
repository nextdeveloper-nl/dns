<?php

namespace NextDeveloper\DNS\Http\Requests\DnsServers;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DnsServersUpdateRequest extends AbstractFormRequest
{
    public function rules()
    {
        return [
            'iaas_cloud_node_id' => 'nullable|string',
            'hostname'           => 'nullable|string',
            'name'               => 'nullable|string',
            'role'               => 'nullable|string|in:primary,secondary',
            'health'             => 'nullable|string',
            'health_summary'     => 'nullable|string',
            'tags'               => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

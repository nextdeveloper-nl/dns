<?php

namespace NextDeveloper\DNS\Http\Requests\DnsServers;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DnsServersCreateRequest extends AbstractFormRequest
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    public function rules()
    {
        return [
            'iaas_cloud_node_id' => 'nullable|string',
            'hostname'           => 'required|string',
            'name'               => 'nullable|string',
            'role'               => 'required|string|in:primary,secondary',
            'agent_uuid'         => 'required|uuid',
            'agent_api_key'      => 'required|string',
            'tags'               => 'nullable',
        ];
    }
}

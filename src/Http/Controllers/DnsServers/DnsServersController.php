<?php

namespace NextDeveloper\DNS\Http\Controllers\DnsServers;

use Illuminate\Http\Request;
use NextDeveloper\DNS\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\DNS\Http\Requests\DnsServers\DnsServersUpdateRequest;
use NextDeveloper\DNS\Database\Filters\DnsServersQueryFilter;
use NextDeveloper\DNS\Database\Models\DnsServers;
use NextDeveloper\DNS\Services\DnsServersService;
use NextDeveloper\DNS\Http\Requests\DnsServers\DnsServersCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;

class DnsServersController extends AbstractController
{
    private $model = DnsServers::class;

    use TagsTrait;

    public function index(DnsServersQueryFilter $filter, Request $request)
    {
        $data = DnsServersService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function getActions()
    {
        $data = DnsServersService::getActions();

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function doAction($objectId, $action)
    {
        $actionId = DnsServersService::doAction($objectId, $action, request()->all());

        return $this->withArray([
            'action_id' => $actionId
        ]);
    }

    public function show($ref)
    {
        $model = DnsServersService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function relatedObjects($ref, $subObject)
    {
        $objects = DnsServersService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    public function store(DnsServersCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation' => 'success'
            ];
        }

        $model = DnsServersService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function update($dnsServersId, DnsServersUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation' => 'success'
            ];
        }

        $model = DnsServersService::update($dnsServersId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function destroy($dnsServersId)
    {
        DnsServersService::delete($dnsServersId);

        return $this->noContent();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

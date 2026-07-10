<?php

namespace NextDeveloper\DNS\Http\Controllers\DnsZones;

use Illuminate\Http\Request;
use NextDeveloper\DNS\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\DNS\Http\Requests\DnsZones\DnsZonesUpdateRequest;
use NextDeveloper\DNS\Database\Filters\DnsZonesQueryFilter;
use NextDeveloper\DNS\Database\Models\DnsZones;
use NextDeveloper\DNS\Services\DnsZonesService;
use NextDeveloper\DNS\Http\Requests\DnsZones\DnsZonesCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;

class DnsZonesController extends AbstractController
{
    private $model = DnsZones::class;

    use TagsTrait;

    public function index(DnsZonesQueryFilter $filter, Request $request)
    {
        $data = DnsZonesService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function getActions()
    {
        $data = DnsZonesService::getActions();

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function doAction($objectId, $action)
    {
        $actionId = DnsZonesService::doAction($objectId, $action, request()->all());

        return $this->withArray([
            'action_id' => $actionId
        ]);
    }

    public function show($ref)
    {
        $model = DnsZonesService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function relatedObjects($ref, $subObject)
    {
        $objects = DnsZonesService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    public function store(DnsZonesCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation' => 'success'
            ];
        }

        $model = DnsZonesService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function update($dnsZonesId, DnsZonesUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation' => 'success'
            ];
        }

        $model = DnsZonesService::update($dnsZonesId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function destroy($dnsZonesId)
    {
        DnsZonesService::delete($dnsZonesId);

        return $this->noContent();
    }

    /**
     * Reconciles the zone's records against its provider - see DnsZonesService::sync().
     */
    public function sync($dnsZonesId)
    {
        $model = DnsZonesService::sync($dnsZonesId);

        return ResponsableFactory::makeResponse($this, $model);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

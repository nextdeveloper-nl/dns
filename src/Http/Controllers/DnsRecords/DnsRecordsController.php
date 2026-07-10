<?php

namespace NextDeveloper\DNS\Http\Controllers\DnsRecords;

use Illuminate\Http\Request;
use NextDeveloper\DNS\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\DNS\Http\Requests\DnsRecords\DnsRecordsUpdateRequest;
use NextDeveloper\DNS\Database\Filters\DnsRecordsQueryFilter;
use NextDeveloper\DNS\Database\Models\DnsRecords;
use NextDeveloper\DNS\Services\DnsRecordsService;
use NextDeveloper\DNS\Http\Requests\DnsRecords\DnsRecordsCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;

class DnsRecordsController extends AbstractController
{
    private $model = DnsRecords::class;

    use TagsTrait;

    public function index(DnsRecordsQueryFilter $filter, Request $request)
    {
        $data = DnsRecordsService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function getActions()
    {
        $data = DnsRecordsService::getActions();

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function doAction($objectId, $action)
    {
        $actionId = DnsRecordsService::doAction($objectId, $action, request()->all());

        return $this->withArray([
            'action_id' => $actionId
        ]);
    }

    public function show($ref)
    {
        $model = DnsRecordsService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function relatedObjects($ref, $subObject)
    {
        $objects = DnsRecordsService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    public function store(DnsRecordsCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation' => 'success'
            ];
        }

        $model = DnsRecordsService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function update($dnsRecordsId, DnsRecordsUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation' => 'success'
            ];
        }

        $model = DnsRecordsService::update($dnsRecordsId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function destroy($dnsRecordsId)
    {
        DnsRecordsService::delete($dnsRecordsId);

        return $this->noContent();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

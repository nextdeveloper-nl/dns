<?php

namespace NextDeveloper\DNS\Http\Controllers\DnsProviderCredentials;

use Illuminate\Http\Request;
use NextDeveloper\DNS\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\DNS\Http\Requests\DnsProviderCredentials\DnsProviderCredentialsUpdateRequest;
use NextDeveloper\DNS\Database\Filters\DnsProviderCredentialsQueryFilter;
use NextDeveloper\DNS\Database\Models\DnsProviderCredentials;
use NextDeveloper\DNS\Services\DnsProviderCredentialsService;
use NextDeveloper\DNS\Http\Requests\DnsProviderCredentials\DnsProviderCredentialsCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;

class DnsProviderCredentialsController extends AbstractController
{
    private $model = DnsProviderCredentials::class;

    use TagsTrait;

    public function index(DnsProviderCredentialsQueryFilter $filter, Request $request)
    {
        $data = DnsProviderCredentialsService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function getActions()
    {
        $data = DnsProviderCredentialsService::getActions();

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function doAction($objectId, $action)
    {
        $actionId = DnsProviderCredentialsService::doAction($objectId, $action, request()->all());

        return $this->withArray([
            'action_id' => $actionId
        ]);
    }

    public function show($ref)
    {
        $model = DnsProviderCredentialsService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function relatedObjects($ref, $subObject)
    {
        $objects = DnsProviderCredentialsService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    public function store(DnsProviderCredentialsCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation' => 'success'
            ];
        }

        $model = DnsProviderCredentialsService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function update($dnsProviderCredentialsId, DnsProviderCredentialsUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation' => 'success'
            ];
        }

        $model = DnsProviderCredentialsService::update($dnsProviderCredentialsId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function destroy($dnsProviderCredentialsId)
    {
        DnsProviderCredentialsService::delete($dnsProviderCredentialsId);

        return $this->noContent();
    }

    /**
     * Re-checks the stored token against the provider on demand (it's also
     * checked automatically on create/update).
     */
    public function verify($dnsProviderCredentialsId)
    {
        $model = DnsProviderCredentialsService::verify($dnsProviderCredentialsId);

        return ResponsableFactory::makeResponse($this, $model);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

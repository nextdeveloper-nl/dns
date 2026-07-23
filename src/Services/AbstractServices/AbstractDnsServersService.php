<?php

namespace NextDeveloper\DNS\Services\AbstractServices;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\Commons\Database\Models\AvailableActions;
use NextDeveloper\DNS\Database\Models\DnsServers;
use NextDeveloper\DNS\Database\Filters\DnsServersQueryFilter;
use NextDeveloper\Commons\Exceptions\ModelNotFoundException;
use NextDeveloper\Commons\Exceptions\NotAllowedException;

/**
 * This class is responsible from managing the data for DnsServers
 *
 * Class AbstractDnsServersService.
 *
 * @package NextDeveloper\DNS\Database\Models
 */
class AbstractDnsServersService
{
    public static function get(?DnsServersQueryFilter $filter = null, array $params = []) : Collection|LengthAwarePaginator
    {
        $enablePaginate = array_key_exists('paginate', $params);

        $request = new Request();

        if($filter == null) {
            $filter = new DnsServersQueryFilter($request);
        }

        $perPage = config('commons.pagination.per_page');

        if($perPage == null) {
            $perPage = 20;
        }

        if(array_key_exists('per_page', $params)) {
            $perPage = intval($params['per_page']);

            if($perPage == 0) {
                $perPage = 20;
            }
        }

        if(array_key_exists('orderBy', $params)) {
            $filter->orderBy($params['orderBy']);
        }

        $model = DnsServers::filter($filter);

        if($enablePaginate) {
            $modelCount = $model->count();
            $page = array_key_exists('page', $params) ? $params['page'] : 1;
            $items = $model->skip(($page - 1) * $perPage)->take($perPage)->get();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $modelCount,
                $perPage,
                $page
            );
        }

        return $model->get();
    }

    public static function getAll()
    {
        return DnsServers::all();
    }

    public static function getByRef($ref) : ?DnsServers
    {
        return DnsServers::findByRef($ref);
    }

    public static function getActions()
    {
        $model = DnsServers::class;

        $model = Str::remove('Database\\Models\\', $model);

        return AvailableActions::where('input', $model)->get();
    }

    public static function doAction($objectId, $action, ...$params)
    {
        $object = DnsServers::where('uuid', $objectId)->first();

        $action = AvailableActions::where('name', $action)
            ->where('input', 'NextDeveloper\DNS\DnsServers')
            ->first();

        $class = $action->class;

        if(class_exists($class)) {
            $action = new $class($object, $params);
            $actionId = $action->getActionId();

            if(request()->get('fg') == 'true') {
                $action->handle();
                return $actionId;
            }

            dispatch($action);

            return $actionId;
        }

        return null;
    }

    public static function getById($id) : ?DnsServers
    {
        return DnsServers::where('id', $id)->first();
    }

    public static function relatedObjects($uuid, $object)
    {
        $obj = DnsServers::where('uuid', $uuid)->first();

        if(!$obj) {
            throw new ModelNotFoundException('Cannot find the related model');
        }

        return $obj->$object;
    }

    public static function create(array $data)
    {
        if (array_key_exists('iam_account_id', $data)) {
            $data['iam_account_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\IAM\Database\Models\Accounts',
                $data['iam_account_id']
            );
        }

        if(!array_key_exists('iam_account_id', $data)) {
            $data['iam_account_id'] = UserHelper::currentAccount()->id;
        }

        if (array_key_exists('iam_user_id', $data)) {
            $data['iam_user_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\IAM\Database\Models\Users',
                $data['iam_user_id']
            );
        }

        if(!array_key_exists('iam_user_id', $data)) {
            $data['iam_user_id'] = UserHelper::me()->id;
        }

        $model = DnsServers::create($data);

        return $model->fresh();
    }

    public static function updateRaw(array $data) : ?DnsServers
    {
        if(array_key_exists('id', $data)) {
            return self::update($data['id'], $data);
        }

        return null;
    }

    public static function update($id, array $data)
    {
        $model = DnsServers::where('uuid', $id)->first();

        if(!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to update. ' .
                'Maybe you dont have the permission to update this object?'
            );
        }

        $model->update($data);

        return $model->fresh();
    }

    public static function delete($id)
    {
        $model = DnsServers::where('uuid', $id)->first();

        if(!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to delete. ' .
                'Maybe you dont have the permission to update this object?'
            );
        }

        return $model->delete();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

}

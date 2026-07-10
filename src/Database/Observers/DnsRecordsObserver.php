<?php

namespace NextDeveloper\DNS\Database\Observers;

use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\Events\Services\Events;

/**
 * Class DnsRecordsObserver
 *
 * @package NextDeveloper\DNS\Database\Observers
 */
class DnsRecordsObserver
{
    public function retrieved(Model $model)
    {
    }

    public function creating(Model $model)
    {
        throw_if(
            !UserHelper::can('create', $model),
            new NotAllowedException('You are not allowed to create this record')
        );

        Events::fire('creating:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function created(Model $model)
    {
        Events::fire('created:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function saving(Model $model)
    {
        throw_if(
            !UserHelper::can('save', $model),
            new NotAllowedException('You are not allowed to save this record')
        );

        Events::fire('saving:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function saved(Model $model)
    {
        Events::fire('saved:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function updating(Model $model)
    {
        throw_if(
            !UserHelper::can('update', $model),
            new NotAllowedException('You are not allowed to update this record')
        );

        Events::fire('updating:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function updated(Model $model)
    {
        Events::fire('updated:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function deleting(Model $model)
    {
        throw_if(
            !UserHelper::can('delete', $model),
            new NotAllowedException('You are not allowed to delete this record')
        );

        Events::fire('deleting:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function deleted(Model $model)
    {
        Events::fire('deleted:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function restoring(Model $model)
    {
        throw_if(
            !UserHelper::can('restore', $model),
            new NotAllowedException('You are not allowed to restore this record')
        );

        Events::fire('restoring:NextDeveloper\DNS\DnsRecords', $model);
    }

    public function restored(Model $model)
    {
        Events::fire('restored:NextDeveloper\DNS\DnsRecords', $model);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

<?php

namespace NextDeveloper\DNS\Authorization\Roles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\IAM\Authorization\Roles\AbstractRole;
use NextDeveloper\IAM\Authorization\Roles\IAuthorizationRole;
use NextDeveloper\IAM\Database\Models\Users;
use NextDeveloper\IAM\Helpers\UserHelper;

class DnsManagerRole extends AbstractRole implements IAuthorizationRole
{
    public const NAME = 'dns-manager';

    public const LEVEL = 150;

    public const DESCRIPTION = 'DNS manager with full CRUD access to zones, records, and provider credentials within their account. Read-only access to PowerDNS server infrastructure.';

    public const DB_PREFIX = 'dns';

    /**
     * Restricts queries to records belonging to the current account.
     */
    public function apply(Builder $builder, Model $model)
    {
        if (DatabaseHelper::isColumnExists($model->getTable(), 'iam_account_id')) {
            $builder->where('iam_account_id', UserHelper::currentAccount()->id);
        }
    }

    public function checkPrivileges(?Users $users = null)
    {
        //
    }

    public function getModule()
    {
        return 'dns';
    }

    public function allowedOperations(): array
    {
        return [
            // Servers — read-only, PowerDNS infrastructure is admin-managed
            'dns_servers:read',

            // Provider credentials — full access within the account
            'dns_provider_credentials:read',
            'dns_provider_credentials:create',
            'dns_provider_credentials:update',
            'dns_provider_credentials:delete',

            // Zones
            'dns_zones:read',
            'dns_zones:create',
            'dns_zones:update',
            'dns_zones:delete',

            // Records
            'dns_records:read',
            'dns_records:create',
            'dns_records:update',
            'dns_records:delete',
        ];
    }

    /**
     * Managers can update any DNS resource that belongs to their account.
     */
    public function checkUpdatePolicy(Model $model, Users $user): bool
    {
        if (UserHelper::hasRole('system-admin')) {
            return true;
        }

        $operation = $model->getTable() . ':update';

        if (in_array('!' . $operation, $this->allowedOperations())) {
            return true;
        }

        if (!in_array($operation, $this->allowedOperations())) {
            return false;
        }

        if (DatabaseHelper::isColumnExists($model->getTable(), 'iam_account_id')) {
            return $model->iam_account_id == UserHelper::currentAccount()->id;
        }

        return true;
    }

    /**
     * Managers can delete any DNS resource that belongs to their account.
     */
    public function checkDeletePolicy(Model $model, Users $user): bool
    {
        if (UserHelper::hasRole('system-admin')) {
            return true;
        }

        $operation = $model->getTable() . ':delete';

        if (in_array('!' . $operation, $this->allowedOperations())) {
            return true;
        }

        if (!in_array($operation, $this->allowedOperations())) {
            return false;
        }

        if (DatabaseHelper::isColumnExists($model->getTable(), 'iam_account_id')) {
            return $model->iam_account_id == UserHelper::currentAccount()->id;
        }

        return true;
    }

    public function getLevel(): int
    {
        return self::LEVEL;
    }

    public function getDescription(): string
    {
        return self::DESCRIPTION;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function canBeApplied(mixed $column): bool
    {
        if (self::DB_PREFIX === '*') {
            return true;
        }

        if (Str::startsWith($column, self::DB_PREFIX)) {
            return true;
        }

        return false;
    }

    public function getDbPrefix()
    {
        return self::DB_PREFIX;
    }

    public function checkRules(Users $_users): bool
    {
        return true;
    }
}

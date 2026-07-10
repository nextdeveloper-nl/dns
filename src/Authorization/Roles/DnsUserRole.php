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

class DnsUserRole extends AbstractRole implements IAuthorizationRole
{
    public const NAME = 'dns-user';

    public const LEVEL = 200;

    public const DESCRIPTION = 'DNS user with CRUD access limited to objects they personally own (iam_user_id) within their account.';

    public const DB_PREFIX = 'dns';

    /**
     * Restricts queries to records owned by the current user, falling back to
     * account scope for tables without iam_user_id (e.g. dns_servers).
     */
    public function apply(Builder $builder, Model $model)
    {
        $hasUserId    = DatabaseHelper::isColumnExists($model->getTable(), 'iam_user_id');
        $hasAccountId = DatabaseHelper::isColumnExists($model->getTable(), 'iam_account_id');

        $builder->where(function (Builder $query) use ($hasUserId, $hasAccountId) {
            if ($hasUserId) {
                $query->where('iam_user_id', UserHelper::me()->id);
            }

            if ($hasAccountId) {
                $query->where('iam_account_id', UserHelper::currentAccount()->id);
            }
        });
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
            // Servers — read-only; users need this to see which PowerDNS server a zone lives on
            'dns_servers:read',

            // Provider credentials — users manage their own CloudFlare tokens
            'dns_provider_credentials:read',
            'dns_provider_credentials:create',
            'dns_provider_credentials:update',
            'dns_provider_credentials:delete',

            // Zones — users manage their own zones
            'dns_zones:read',
            'dns_zones:create',
            'dns_zones:update',
            'dns_zones:delete',

            // Records — users manage their own records
            'dns_records:read',
            'dns_records:create',
            'dns_records:update',
            'dns_records:delete',
        ];
    }

    public function checkUpdatePolicy(Model $model, Users $user): bool
    {
        if (UserHelper::hasRole('system-admin')) {
            return true;
        }

        $operation = $model->getTable() . ':update';

        if (!in_array($operation, $this->allowedOperations())) {
            return false;
        }

        if (DatabaseHelper::isColumnExists($model->getTable(), 'iam_user_id')) {
            return $model->iam_user_id == UserHelper::me()->id;
        }

        return true;
    }

    public function checkDeletePolicy(Model $model, Users $user): bool
    {
        if (UserHelper::hasRole('system-admin')) {
            return true;
        }

        $operation = $model->getTable() . ':delete';

        if (!in_array($operation, $this->allowedOperations())) {
            return false;
        }

        if (DatabaseHelper::isColumnExists($model->getTable(), 'iam_user_id')) {
            return $model->iam_user_id == UserHelper::me()->id;
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

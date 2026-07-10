<?php

namespace NextDeveloper\DNS\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class DnsZonesQueryFilter extends AbstractQueryFilter
{
    /**
     * @var Builder
     */
    protected $builder;

    public function tags($values)
    {
        $tags = explode(',', $values);

        $search = '';

        for($i = 0; $i < count($tags); $i++) {
            $search .= "'" . trim($tags[$i]) . "',";
        }

        $search = substr($search, 0, -1);

        return $this->builder->whereRaw('tags @> ARRAY[' . $search . ']');
    }

    public function name($value)
    {
        return $this->builder->where('name', 'ilike', '%' . $value . '%');
    }

    public function provider($value)
    {
        return $this->builder->where('provider', '=', $value);
    }

    public function status($value)
    {
        return $this->builder->where('status', 'ilike', '%' . $value . '%');
    }

    public function isDnssecEnabled($value)
    {
        return $this->builder->where('is_dnssec_enabled', '=', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    public function is_dnssec_enabled($value)
    {
        return $this->isDnssecEnabled($value);
    }

    public function dnsServerId($value)
    {
        $dnsServer = \NextDeveloper\DNS\Database\Models\DnsServers::where('uuid', $value)->first();

        if($dnsServer) {
            return $this->builder->where('dns_server_id', '=', $dnsServer->id);
        }
    }

    public function dns_server_id($value)
    {
        return $this->dnsServerId($value);
    }

    public function createdAtStart($date)
    {
        return $this->builder->where('created_at', '>=', $date);
    }

    public function createdAtEnd($date)
    {
        return $this->builder->where('created_at', '<=', $date);
    }

    public function created_at_start($value)
    {
        return $this->createdAtStart($value);
    }

    public function created_at_end($value)
    {
        return $this->createdAtEnd($value);
    }

    public function updatedAtStart($date)
    {
        return $this->builder->where('updated_at', '>=', $date);
    }

    public function updatedAtEnd($date)
    {
        return $this->builder->where('updated_at', '<=', $date);
    }

    public function updated_at_start($value)
    {
        return $this->updatedAtStart($value);
    }

    public function updated_at_end($value)
    {
        return $this->updatedAtEnd($value);
    }

    public function iamAccountId($value)
    {
        $iamAccount = \NextDeveloper\IAM\Database\Models\Accounts::where('uuid', $value)->first();

        if($iamAccount) {
            return $this->builder->where('iam_account_id', '=', $iamAccount->id);
        }
    }

    public function iamUserId($value)
    {
        $iamUser = \NextDeveloper\IAM\Database\Models\Users::where('uuid', $value)->first();

        if($iamUser) {
            return $this->builder->where('iam_user_id', '=', $iamUser->id);
        }
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

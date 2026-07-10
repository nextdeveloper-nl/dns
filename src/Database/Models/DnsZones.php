<?php

namespace NextDeveloper\DNS\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\DNS\Database\Observers\DnsZonesObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\HasStates;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * DnsZones model. provider ('powerdns'|'cloudflare') is the discriminator
 * DnsProviderManager reads to resolve which adapter owns this zone.
 *
 * @package  NextDeveloper\DNS\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $name
 * @property string $provider
 * @property integer $dns_server_id
 * @property integer $dns_provider_credential_id
 * @property string $soa_primary_ns
 * @property string $soa_admin_email
 * @property integer $soa_serial
 * @property integer $soa_refresh
 * @property integer $soa_retry
 * @property integer $soa_expire
 * @property integer $soa_ttl
 * @property boolean $is_dnssec_enabled
 * @property array $dnssec_ds_records
 * @property string $status
 * @property string $last_error
 * @property array $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class DnsZones extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 'dns_zones';

    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            'iam_account_id',
            'iam_user_id',
            'name',
            'provider',
            'external_id',
            'dns_server_id',
            'dns_provider_credential_id',
            'soa_primary_ns',
            'soa_admin_email',
            'soa_serial',
            'soa_refresh',
            'soa_retry',
            'soa_expire',
            'soa_ttl',
            'is_dnssec_enabled',
            'dnssec_ds_records',
            'status',
            'last_error',
            'tags',
    ];

    /**
     @var array
     */
    protected $casts = [
    'id' => 'integer',
    'iam_account_id' => 'integer',
    'iam_user_id' => 'integer',
    'name' => 'string',
    'provider' => 'string',
    'external_id' => 'string',
    'dns_server_id' => 'integer',
    'dns_provider_credential_id' => 'integer',
    'soa_primary_ns' => 'string',
    'soa_admin_email' => 'string',
    'soa_serial' => 'integer',
    'soa_refresh' => 'integer',
    'soa_retry' => 'integer',
    'soa_expire' => 'integer',
    'soa_ttl' => 'integer',
    'is_dnssec_enabled' => 'boolean',
    'dnssec_ds_records' => \NextDeveloper\Commons\Database\Casts\TextArray::class,
    'status' => 'string',
    'last_error' => 'string',
    'tags' => \NextDeveloper\Commons\Database\Casts\TextArray::class,
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
    ];

    /**
     @var int
     */
    protected $perPage = 20;

    /**
     @return void
     */
    public static function boot()
    {
        parent::boot();

        parent::observe(DnsZonesObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('dns.scopes.global');
        $modelScopes = config('dns.scopes.dns_zones');

        if(!$modelScopes) { $modelScopes = [];
        }
        if (!$globalScopes) { $globalScopes = [];
        }

        $scopes = array_merge(
            $globalScopes,
            $modelScopes
        );

        if($scopes) {
            foreach ($scopes as $scope) {
                static::addGlobalScope(app($scope));
            }
        }
    }

    public function dnsServer() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\DNS\Database\Models\DnsServers::class, 'dns_server_id');
    }

    public function dnsProviderCredential() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\DNS\Database\Models\DnsProviderCredentials::class, 'dns_provider_credential_id');
    }

    public function records() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\DNS\Database\Models\DnsRecords::class, 'dns_zone_id');
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

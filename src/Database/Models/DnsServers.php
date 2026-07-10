<?php

namespace NextDeveloper\DNS\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\DNS\Database\Observers\DnsServersObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * DnsServers model - represents a single PowerDNS instance (primary/secondary),
 * identified to the platform by its pdns-agent's agent_uuid.
 *
 * @package  NextDeveloper\DNS\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property integer $iaas_cloud_node_id
 * @property string $hostname
 * @property string $name
 * @property string $role
 * @property string $agent_uuid
 * @property string $agent_api_key
 * @property string $agent_version
 * @property string $pdns_version
 * @property string $agent_status
 * @property \Carbon\Carbon $agent_last_seen_at
 * @property \Carbon\Carbon $agent_connected_at
 * @property string $health
 * @property string $health_summary
 * @property array $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class DnsServers extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 'dns_servers';

    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            'iam_account_id',
            'iam_user_id',
            'iaas_cloud_node_id',
            'hostname',
            'name',
            'role',
            'agent_uuid',
            'agent_api_key',
            'agent_version',
            'pdns_version',
            'agent_status',
            'agent_last_seen_at',
            'agent_connected_at',
            'health',
            'health_summary',
            'tags',
    ];

    /**
     @var array
     */
    protected $casts = [
    'id' => 'integer',
    'iam_account_id' => 'integer',
    'iam_user_id' => 'integer',
    'iaas_cloud_node_id' => 'integer',
    'hostname' => 'string',
    'name' => 'string',
    'role' => 'string',
    'agent_uuid' => 'string',
    'agent_api_key' => 'string',
    'agent_version' => 'string',
    'pdns_version' => 'string',
    'agent_status' => 'string',
    'agent_last_seen_at' => 'datetime',
    'agent_connected_at' => 'datetime',
    'health' => 'string',
    'health_summary' => 'string',
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

        parent::observe(DnsServersObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('dns.scopes.global');
        $modelScopes = config('dns.scopes.dns_servers');

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

    public function zones() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\DNS\Database\Models\DnsZones::class, 'dns_server_id');
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

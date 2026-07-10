<?php

namespace NextDeveloper\DNS\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\DNS\Database\Observers\DnsRecordsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\HasStates;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * DnsRecords model.
 *
 * @package  NextDeveloper\DNS\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $dns_zone_id
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $type
 * @property string $name
 * @property string $content
 * @property integer $ttl
 * @property integer $priority
 * @property boolean $is_proxied
 * @property string $status
 * @property string $last_error
 * @property array $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class DnsRecords extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 'dns_records';

    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            'dns_zone_id',
            'iam_account_id',
            'iam_user_id',
            'type',
            'name',
            'content',
            'external_id',
            'ttl',
            'priority',
            'is_proxied',
            'status',
            'last_error',
            'tags',
    ];

    /**
     @var array
     */
    protected $casts = [
    'id' => 'integer',
    'dns_zone_id' => 'integer',
    'iam_account_id' => 'integer',
    'iam_user_id' => 'integer',
    'type' => 'string',
    'name' => 'string',
    'content' => 'string',
    'external_id' => 'string',
    'ttl' => 'integer',
    'priority' => 'integer',
    'is_proxied' => 'boolean',
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

        parent::observe(DnsRecordsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('dns.scopes.global');
        $modelScopes = config('dns.scopes.dns_records');

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

    public function zone() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\DNS\Database\Models\DnsZones::class, 'dns_zone_id');
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

<?php

namespace NextDeveloper\DNS\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\DNS\Database\Observers\DnsProviderCredentialsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * DnsProviderCredentials model - a customer's own CloudFlare (or future third-party
 * provider) API token, stored encrypted. PowerDNS zones never use this model - they
 * authenticate via the owning DnsServers.agent_uuid instead.
 *
 * @package  NextDeveloper\DNS\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $provider
 * @property string $api_token_enc
 * @property string $cloudflare_account_id
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $last_verified_at
 * @property string $last_verify_error
 * @property array $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class DnsProviderCredentials extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 'dns_provider_credentials';

    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            'iam_account_id',
            'iam_user_id',
            'provider',
            'api_token_enc',
            'cloudflare_account_id',
            'name',
            'status',
            'last_verified_at',
            'last_verify_error',
            'tags',
    ];

    /**
     @var array
     */
    protected $casts = [
    'id' => 'integer',
    'iam_account_id' => 'integer',
    'iam_user_id' => 'integer',
    'provider' => 'string',
    // Laravel encrypts/decrypts this attribute transparently - never touches the
    // database in plaintext, never appears in logs/dumps of the raw attribute array
    // without the app key.
    'api_token_enc' => 'encrypted',
    'cloudflare_account_id' => 'string',
    'name' => 'string',
    'status' => 'string',
    'last_verified_at' => 'datetime',
    'last_verify_error' => 'string',
    'tags' => \NextDeveloper\Commons\Database\Casts\TextArray::class,
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
    ];

    /**
     * api_token_enc holds the customer's real CloudFlare API token - never expose
     * it through the API transformer.
     *
     @var array
     */
    protected $hidden = [
        'api_token_enc',
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

        parent::observe(DnsProviderCredentialsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('dns.scopes.global');
        $modelScopes = config('dns.scopes.dns_provider_credentials');

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
        return $this->hasMany(\NextDeveloper\DNS\Database\Models\DnsZones::class, 'dns_provider_credential_id');
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}

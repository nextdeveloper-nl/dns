<?php

namespace NextDeveloper\DNS\Services;

use Illuminate\Support\Facades\Http;
use NextDeveloper\DNS\Database\Models\DnsProviderCredentials;
use NextDeveloper\DNS\Services\AbstractServices\AbstractDnsProviderCredentialsService;

/**
 * This class is responsible from managing the data for DnsProviderCredentials
 *
 * Class DnsProviderCredentialsService.
 *
 * @package NextDeveloper\DNS\Database\Models
 */
class DnsProviderCredentialsService extends AbstractDnsProviderCredentialsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * The API accepts a plain "api_token" field; the column is named
     * api_token_enc (encrypted cast) to make its sensitivity obvious at the
     * schema level, so remap it here before it reaches the abstract create().
     */
    public static function create(array $data)
    {
        if (array_key_exists('api_token', $data)) {
            $data['api_token_enc'] = $data['api_token'];
            unset($data['api_token']);
        }

        $model = parent::create($data);

        static::verify($model->uuid);

        return $model->fresh();
    }

    public static function update($id, array $data)
    {
        if (array_key_exists('api_token', $data)) {
            $data['api_token_enc'] = $data['api_token'];
            unset($data['api_token']);
        }

        $model = parent::update($id, $data);

        if (array_key_exists('api_token_enc', $data)) {
            static::verify($model->uuid);
        }

        return $model->fresh();
    }

    /**
     * Confirms the stored token is actually valid against the provider, so the
     * customer finds out immediately rather than at first zone-create attempt.
     */
    public static function verify(string $id): DnsProviderCredentials
    {
        $model = DnsProviderCredentials::where('uuid', $id)->firstOrFail();

        if ($model->provider !== 'cloudflare') {
            return $model;
        }

        $response = Http::withToken($model->api_token_enc)
            ->baseUrl(config('dns.cloudflare.api_base'))
            ->acceptJson()
            ->get('/user/tokens/verify');

        if ($response->successful() && $response->json('success') === true) {
            $model->update([
                'status'            => 'active',
                'last_verified_at'  => now(),
                'last_verify_error' => null,
            ]);
        } else {
            $errors = $response->json('errors', []);

            $model->update([
                'status'            => 'invalid',
                'last_verified_at'  => now(),
                'last_verify_error' => $errors[0]['message'] ?? $response->body(),
            ]);
        }

        return $model->fresh();
    }
}

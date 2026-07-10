<?php

use NextDeveloper\DNS\Services\DnsProviders\CloudFlare\CloudFlareProviderService;
use NextDeveloper\DNS\Services\DnsProviders\PowerDns\PowerDnsProviderService;

return [
    'scopes'    =>  [
        'global' => [
            '\NextDeveloper\IAM\Database\Scopes\AuthorizationScope',
            '\NextDeveloper\Commons\Database\GlobalScopes\LimitScope',
        ]
    ],

    // Registered DNS provider adapters - keyed by dns_zones.provider / dns_provider_credentials.provider.
    // DnsProviderManager resolves DnsZones/DnsRecords operations through whichever adapter
    // matches the zone's provider column - see src/Services/DnsProviderManager.php.
    'providers' => [
        'powerdns'   => [
            'adapter' => PowerDnsProviderService::class,
        ],
        'cloudflare' => [
            'adapter' => CloudFlareProviderService::class,
        ],
    ],

    // How long AgentCommandsService::dispatch() waits for the pdns-agent to reply
    // before a zone/record operation is considered timed out.
    'agent_command_timeout_s' => env('DNS_AGENT_COMMAND_TIMEOUT_S', 60),

    // CloudFlare API base - overridable for testing against a mock server.
    'cloudflare' => [
        'api_base' => env('DNS_CLOUDFLARE_API_BASE', 'https://api.cloudflare.com/client/v4'),
    ],
];

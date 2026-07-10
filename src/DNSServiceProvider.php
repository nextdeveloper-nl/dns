<?php

namespace NextDeveloper\DNS;

use NextDeveloper\Commons\AbstractServiceProvider;
use NextDeveloper\DNS\Services\DnsProviderManager;
use NextDeveloper\DNS\Services\DnsProviders\CloudFlare\CloudFlareProviderService;
use NextDeveloper\DNS\Services\DnsProviders\PowerDns\PowerDnsProviderService;

/**
 * Class DNSServiceProvider
 *
 * @package NextDeveloper\DNS
 */
class DNSServiceProvider extends AbstractServiceProvider
{
    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(
            [
            __DIR__.'/../config/dns.php' => config_path('dns.php'),
            ], 'config'
        );
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->registerHelpers();
        $this->registerRoutes();
        $this->registerCommands();

        $this->mergeConfigFrom(__DIR__.'/../config/dns.php', 'dns');

        $this->app->singleton(DnsProviderManager::class, function () {
            $manager = new DnsProviderManager();

            foreach (config('dns.providers', []) as $provider => $providerConfig) {
                $manager->registerAdapter($provider, $providerConfig['adapter']);
            }

            return $manager;
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['events'];
    }

    /**
     * Register module routes
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if ( ! $this->app->routesAreCached() && config('leo.allowed_routes.dns', true) ) {
            $this->app['router']
                ->namespace('NextDeveloper\DNS\Http\Controllers')
                ->group(__DIR__.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'api.routes.php');
        }
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Registers module based commands.
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \NextDeveloper\DNS\Console\Commands\ListenPdnsAgentEvents::class,
            ]);
        }
    }
}

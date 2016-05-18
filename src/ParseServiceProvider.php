<?php

namespace Parziphal\Parse;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Parziphal\Parse\ParseUserProvider;
use Parziphal\Parse\ParseGuard;
use Parziphal\Parse\SessionStorage;
use Parse\ParseClient;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Foundation\Application as LaravelApplication;

class ParseServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();

        $this->setupParse();
    }
   
    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config/parse.php');

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('parse.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('parse');
        }

        $this->mergeConfigFrom($source, 'parse');
    }

    /**
     * Setup parse.
     *
     * @return void
     */ 
    protected function setupParse()
    {
        $config = $this->app->config->get('parse');
        
        Auth::provider('parse', function($app, array $config) {
            return new ParseUserProvider($config['model']);
        });
        
        // UserModel::setCurrentUserModel($config['user_class']);
        
        ParseClient::setStorage(new SessionStorage());
        
        ParseClient::initialize($config['app_id'], $config['rest_key'], $config['master_key']);

        ParseClient::setServerURL($config['server_url']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            //
        ];
    }
}

<?php

namespace Parziphal\Parse;

use Parse\ParseClient;
use Parziphal\Parse\SessionStorage;
use Illuminate\Support\Facades\Auth;
use Parziphal\Parse\ParseUserProvider;
use Illuminate\Support\ServiceProvider;
use Parziphal\Parse\Console\ModelMakeCommand;
use Parziphal\Parse\Auth\Providers\UserProvider;
use Laravel\Lumen\Application as LumenApplication;
use Parziphal\Parse\Auth\Providers\AnyUserProvider;
use Parziphal\Parse\Auth\Providers\FacebookUserProvider;
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
        
        $this->registerCommands();
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
    
    protected function registerCommands()
    {
        $this->registerModelMakeCommand();
        
        $this->commands('command.parse.model.make');
    }
    
    protected function registerModelMakeCommand()
    {
        $this->app->singleton('command.parse.model.make', function ($app) {
            return new ModelMakeCommand($app['files']);
        });
    }

    /**
     * Setup parse.
     *
     * @return void
     */
    protected function setupParse()
    {
        $config = $this->app->config->get('parse');
        
        ParseClient::setStorage(new SessionStorage());
        ParseClient::initialize($config['app_id'], $config['rest_key'], $config['master_key']);
        ParseClient::setServerURL($config['server_url']);
        
        Auth::provider('parse', function($app, array $config) {
            return new UserProvider($config['model']);
        });
        
        Auth::provider('parse-facebook', function($app, array $config) {
            return new FacebookUserProvider($config['model']);
        });
        
        Auth::provider('parse-any', function($app, array $config) {
            return new AnyUserProvider($config['model']);
        });
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

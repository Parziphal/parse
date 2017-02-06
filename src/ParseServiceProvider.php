<?php

namespace Parziphal\Parse;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use Parse\ParseClient;
use Parziphal\Parse\Auth\Providers\AnyUserProvider;
use Parziphal\Parse\Auth\Providers\FacebookUserProvider;
use Parziphal\Parse\Auth\Providers\UserProvider;
use Parziphal\Parse\Console\ModelMakeCommand;
use Parziphal\Parse\Validation\ParsePresenceVerifier;

class ParseServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig ();

        $this->registerCommands ();

        $this->setupParse ();
    }

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath (__DIR__ . '/../config/parse.php');

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole ()) {
            $this->publishes ([$source => config_path ('parse.php')], 'parse');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure ('parse');
        }

        $this->mergeConfigFrom ($source, 'parse');
    }

    protected function registerCommands()
    {
        $this->registerModelMakeCommand ();

        $this->commands ('command.parse.model.make');
    }

    protected function registerModelMakeCommand()
    {
        $this->app->singleton ('command.parse.model.make', function ($app) {
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
        $config = $this->app->config->get ('parse');

        ParseClient::setStorage (new SessionStorage());
        ParseClient::initialize ($config['app_id'], $config['rest_key'], $config['master_key']);
        ParseClient::setServerURL ($config['server_url'], $config['mount_path']);

        Auth::provider ('parse', function () {
            return new UserProvider(config ('auth.providers.users.model'));
        });

        Auth::provider ('parse-facebook', function () {
            return new FacebookUserProvider(config ('auth.providers.users.model'));
        });

        Auth::provider ('parse-any', function () {
            return new AnyUserProvider(config ('auth.providers.users.model'));
        });

        $this->app->extend ('validation.presence', function () {
            return new ParsePresenceVerifier();
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

<?php

namespace Illuminate\Parse\Console;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Console\Command;

class AuthMakeCommand extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:auth {--views : Only scaffold the authentication views}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic login and registration views and routes';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
        'auth/login.stub' => 'auth/login.blade.php',
        'auth/register.stub' => 'auth/register.blade.php',
        'auth/passwords/email.stub' => 'auth/passwords/email.blade.php',
        'auth/passwords/reset.stub' => 'auth/passwords/reset.blade.php',
        'layouts/app.stub' => 'layouts/app.blade.php',
        'home.stub' => 'home.blade.php',
    ];

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $controllers = [
        'Auth/ForgotPasswordController',
        'Auth/LoginController',
        'Auth/RegisterController',
        'Auth/ResetPasswordController',
        'HomeController'
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->createDirectories ();

        $this->exportViews ();

        if (!$this->option ('views')) {
            foreach($this->controllers as $filename) {
                file_put_contents (
                    app_path ('Http/Controllers/' . $filename . '.php'),
                    $this->compileControllerStub ($filename)
                );
            }

            file_put_contents (
                base_path ('routes/web.php'),
                file_get_contents (__DIR__ . '/stubs/auth/routes.stub'),
                FILE_APPEND
            );
        }

        $this->info ('Authentication scaffolding generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir (app_path ('Http/Controllers/Auth'))) {
            mkdir (base_path ('Http/Controllers/Auth'), 0755, true);
        }

        if (!is_dir (base_path ('resources/views/layouts'))) {
            mkdir (base_path ('resources/views/layouts'), 0755, true);
        }

        if (!is_dir (base_path ('resources/views/auth/passwords'))) {
            mkdir (base_path ('resources/views/auth/passwords'), 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            copy (
                __DIR__ . '/stubs/auth/views/' . $key,
                base_path ('resources/views/' . $value)
            );
        }
    }

    /**
     * Compiles the HomeController stub.
     *
     * @param $filename
     * @return string
     */
    protected function compileControllerStub($filename)
    {
        return str_replace (
            'DummyNamespace',
            $this->getAppNamespace (),
            file_get_contents (__DIR__ . '/stubs/auth/controllers/' . $filename . '.stub')
        );
    }
}

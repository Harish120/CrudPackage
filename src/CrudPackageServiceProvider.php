<?php

namespace Harish\CrudPackage;

use Illuminate\Support\ServiceProvider;

class CrudPackageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the package's command
        $this->commands([
            \Harish\CrudPackage\Console\CrudCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config, views, migrations, etc. if needed
    }
}

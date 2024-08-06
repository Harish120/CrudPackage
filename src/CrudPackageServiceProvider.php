<?php

namespace Harry\CrudPackage;

use Harry\CrudPackage\Console\CrudCommand;
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
            CrudCommand::class,
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

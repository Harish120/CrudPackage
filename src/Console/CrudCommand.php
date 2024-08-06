<?php

// packages/Harish/CrudPackage/src/Console/CrudCommand.php

namespace Harish\CrudPackage\Console;

use Illuminate\Console\Command;

class CrudCommand extends Command
{
    protected $signature = 'crud:generate {model}';
    protected $description = 'Generate CRUD operations for a specified model';

    public function handle()
    {
        $modelName = $this->argument('model');
        $this->info("Generating CRUD for model: $modelName");

        // Generate the model, migration, controller, and routes
        $this->generateModel($modelName);
        $this->generateMigration($modelName);
        $this->generateController($modelName);
        $this->generateRoutes($modelName);

        $this->info("CRUD generation for $modelName complete.");
    }

    protected function generateModel($modelName)
    {
        $this->call('make:model', ['name' => $modelName, '--migration' => true]);
    }

    protected function generateMigration($modelName)
    {
        $this->call('make:migration', ['name' => "create_{$modelName}_table"]);
    }

    protected function generateController($modelName)
    {
        $this->call('make:controller', [
            'name' => "{$modelName}Controller",
            '--resource' => true,
        ]);
    }

    protected function generateRoutes($modelName)
    {
        $route = "Route::resource('".strtolower($modelName)."', '{$modelName}Controller');";
        file_put_contents(base_path('routes/web.php'), $route.PHP_EOL, FILE_APPEND);
    }
}

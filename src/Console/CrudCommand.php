<?php
namespace Harry\CrudPackage\Console;

use Harry\CrudPackage\Console\Commands\ControllerGenerator;
use Harry\CrudPackage\Console\Commands\MigrationGenerator;
use Harry\CrudPackage\Console\Commands\ModelGenerator;
use Harry\CrudPackage\Console\Commands\RouteGenerator;
use Illuminate\Console\Command;

class CrudCommand extends Command
{
    protected $signature = 'crud:generate {model} {--columns=}';
    protected $description = 'Generate CRUD operations for a specified model';

    public function handle()
    {
        $modelName = $this->argument('model');
        $columns = $this->option('columns');
        $this->info("Generating CRUD for model: $modelName");

        $modelGenerator = new ModelGenerator($this);
        $modelGenerator->generate($modelName, $columns);

        $migrationGenerator = new MigrationGenerator($this);
        $migrationGenerator->generate($modelName);

        $controllerGenerator = new ControllerGenerator($this);
        $controllerGenerator->generate($modelName);

        $routeGenerator = new RouteGenerator($this);
        $routeGenerator->generate($modelName);

        $this->info("CRUD generation for $modelName complete.");
    }
}

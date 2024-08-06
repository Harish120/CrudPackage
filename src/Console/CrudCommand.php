<?php
namespace Harry\CrudPackage\Console;

use harry\CrudPackage\Commands\ControllerGenerator;
use harry\CrudPackage\Commands\MigrationGenerator;
use harry\CrudPackage\Commands\RouteGenerator;
use Illuminate\Console\Command;

class CrudCommand extends Command
{
    protected $signature = 'crud:generate {model} {--columns=}';
    protected $description = 'Generate CRUD operations for a specified model';

    public function handle()
    {
        $modelName = $this->argument('model');
        $this->info("Generating CRUD for model: $modelName");

        $migrationGenerator = new MigrationGenerator($this);
        $migrationGenerator->generate($modelName);

        $controllerGenerator = new ControllerGenerator($this);
        $controllerGenerator->generate($modelName);

        $routeGenerator = new RouteGenerator($this);
        $routeGenerator->generate($modelName);

        $this->info("CRUD generation for $modelName complete.");
    }
}

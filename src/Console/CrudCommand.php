<?php
namespace Harryes\CrudPackage\Console;

use Harryes\CrudPackage\Console\Commands\ControllerGenerator;
use Harryes\CrudPackage\Console\Commands\MigrationGenerator;
use Harryes\CrudPackage\Console\Commands\ModelGenerator;
use Harryes\CrudPackage\Console\Commands\RouteGenerator;
use Illuminate\Console\Command;

class CrudCommand extends Command
{
    protected $signature = 'crud:generate {model} {--columns=}';
    protected $description = 'Generate CRUD operations for a specified model';

    public function handle(): void
    {
        $modelName = $this->argument('model');
        $columns = $this->option('columns');

        if ($columns === '') {
            $this->error('Error: The --columns option cannot be empty.');
            return;
        }

        // Validate the columns format
        $columnPattern = '/^(\w+:(string|integer|boolean|text|date|file)(\?)?(,)?)+$/';
        if (!preg_match($columnPattern, $columns)) {
            $this->error('Error: Invalid column format. Correct format is name:type,example:string?');
            return;
        }

        $this->info("Generating CRUD for model: $modelName");
        $this->info("");

        $modelGenerator = new ModelGenerator($this);
        $modelGenerator->generate($modelName, $columns);

        $migrationGenerator = new MigrationGenerator($this);
        $migrationGenerator->generate($modelName);

        $controllerGenerator = new ControllerGenerator($this);
        $controllerGenerator->generate($modelName);

        $routeGenerator = new RouteGenerator($this);
        $routeGenerator->generate($modelName);

        $this->info("");
        $this->info("CRUD generation for $modelName complete.");
    }
}

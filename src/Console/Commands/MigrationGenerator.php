<?php

namespace Harry\CrudPackage\Commands;

use Harry\CrudPackage\Helpers\FileHelper;
use Illuminate\Support\Str;

class MigrationGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName)
    {
        $tableName = Str::plural(strtolower($modelName));
        $migrationName = "create_{$tableName}_table";

        $this->command->call('make:migration', ['name' => $migrationName]);

        $columns = $this->command->option('columns');
        if ($columns) {
            $columnArray = explode(',', $columns);
            $migrationFile = $this->getMigrationFile($migrationName);
            $this->updateMigrationFile($migrationFile, $columnArray);
        }
    }

    protected function getMigrationFile($migrationName)
    {
        $timestamp = date('Y_m_d_His');
        return database_path("migrations/{$timestamp}_{$migrationName}.php");
    }

    protected function updateMigrationFile($migrationFile, $columns)
    {
        $migrationContent = file_get_contents($migrationFile);
        $columnDefinitions = '';

        foreach ($columns as $column) {
            $parts = explode(':', $column);
            $name = $parts[0];
            $type = $parts[1];
            $columnDefinitions .= "\$table->$type('$name');\n\t\t\t";
        }

        $migrationContent = str_replace(
            '$table->id();',
            "\$table->id();\n\t\t\t" . $columnDefinitions,
            $migrationContent
        );

        FileHelper::write($migrationFile, $migrationContent);
    }
}

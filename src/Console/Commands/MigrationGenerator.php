<?php

namespace Harryes\CrudPackage\Console\Commands;

use Harryes\CrudPackage\Helpers\FileHelper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName): void
    {
        $tableName = Str::plural(Str::snake($modelName))        ;
        $migrationName = "create_{$tableName}_table";
        $timestamp = date('Y_m_d_His');
        $migrationFileName = "{$timestamp}_{$migrationName}.php";
        $migrationFilePath = database_path("migrations/{$migrationFileName}");

        // Path to the stub
        $stubPath = __DIR__ . '/../../stubs/migration.stub';

        // Check if the stub exists
        if (!FileHelper::exists($stubPath)) {
            $this->command->error("    Migration stub file not found: {$stubPath}");
            return;
        }

        // Get the stub content
        $stubContent = FileHelper::read($stubPath);

        $columns = $this->command->option('columns');
        $columnDefinitions = null;
        if($columns) {
            $columnDefinitions = $this->generateColumnDefinitions($columns);
        }

        // Replace placeholders in the stub
        $migrationContent = str_replace(
            ['{{ tableName }}', '{{ columns }}'],
            [$tableName, $columnDefinitions],
            $stubContent
        );

        // Write the new migration file
        FileHelper::write($migrationFilePath, $migrationContent);

        $this->command->info("    Migration [{$migrationFilePath}] created successfully.");
    }

    protected function generateColumnDefinitions($columns): string
    {
        $columnArray = explode(',', $columns);
        $columnDefinitions = '';

        foreach ($columnArray as $column) {
            $parts = explode(':', $column);
            $name = $parts[0];
            $type = $parts[1];
            $nullable = false;
            $hasDefaultValue = false;
            $defaultValue = 0;

            if (Str::endsWith($type, '?')) {
                $nullable = true;
                $type = rtrim($type, '?');
            } else if(Str::contains($type, '*')) {
                $defaultString = explode('*', $type);
                $type = $defaultString[0];
                if(isset($defaultString[1])) {
                    $defaultValue = $defaultString[1];
                    $hasDefaultValue = true;
                }
            }
            if ($type === 'file') {
                $type = 'string'; // Store file paths as strings
            }
            $nullableDefinition = $nullable ? '->nullable()' : '';
            $defaultDefinition = $hasDefaultValue ? "->default('{$defaultValue}')" : '';
            $columnDefinitions .= "\$table->$type('$name'){$nullableDefinition}{$defaultDefinition};\n\t\t\t";
        }

        return $columnDefinitions;
    }
}

<?php

namespace Harry\CrudPackage\Console\Commands;

use Harry\CrudPackage\Helpers\FileHelper;
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
        $tableName = Str::plural(strtolower($modelName));
        $migrationName = "create_{$tableName}_table";
        $timestamp = date('Y_m_d_His');
        $migrationFileName = "{$timestamp}_{$migrationName}.php";
        $migrationFilePath = database_path("migrations/{$migrationFileName}");

        // Path to the stub
        $stubPath = __DIR__ . '/../../stubs/migration.stub';

        // Check if the stub exists
        if (!FileHelper::exists($stubPath)) {
            $this->command->error("Migration stub file not found: {$stubPath}");
            return;
        }

        // Get the stub content
        $stubContent = FileHelper::read($stubPath);

        $columns = $this->command->option('columns');
        $columnDefinitions = $this->generateColumnDefinitions($columns);

        // Replace placeholders in the stub
        $migrationContent = str_replace(
            ['{{ tableName }}', '{{ columns }}'],
            [$tableName, $columnDefinitions],
            $stubContent
        );

        // Write the new migration file
        FileHelper::write($migrationFilePath, $migrationContent);

        $this->command->info("Migration [{$migrationFilePath}] created successfully.");
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

            if (str_ends_with($type, '?')) {
                $nullable = true;
                $type = rtrim($type, '?');
            }
            $nullableDefinition = $nullable ? '->nullable()' : '';
            $columnDefinitions .= "\$table->$type('$name'){$nullableDefinition};\n\t\t\t";
        }

        return $columnDefinitions;
    }
}

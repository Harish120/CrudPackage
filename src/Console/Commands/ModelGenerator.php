<?php

namespace Harry\CrudPackage\Console\Commands;

use Harry\CrudPackage\Helpers\FileHelper;

class ModelGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName, $columns): void
    {
        // Path to the stub
        $stubPath = __DIR__ . '/../../stubs/model.stub';

        // Check if the stub exists
        if (!FileHelper::exists($stubPath)) {
            $this->command->error("    Model stub file not found: {$stubPath}");
            return;
        }

        // Get the stub content
        $stubContent = FileHelper::read($stubPath);

        // query trait
        $queryTrait = 'HandlesQuery';

        // Generate fillable array
        $fillable = $this->generateFillable($columns);

        // Replace placeholders in the stub
        $modelContent = str_replace(
            ['{{ modelName }}', '{{ queryTrait }}', '{{ fillable }}'],
            [$modelName, $queryTrait, $fillable],
            $stubContent
        );

        // Write the new model file
        $modelFile = app_path("Models/{$modelName}.php");

        FileHelper::write($modelFile, $modelContent);
        $this->command->info("    Model [{$modelFile}] created successfully.");
    }

    protected function generateFillable($columns): string
    {
        $columnsArray = explode(',', $columns);
        $fillableArray = array_map(function ($column) {
            // Remove anything after ':' if it exists
            return "'" . explode(':', $column)[0] . "'";
        }, $columnsArray);

        return '[' . implode(', ', $fillableArray) . ']';
    }
}

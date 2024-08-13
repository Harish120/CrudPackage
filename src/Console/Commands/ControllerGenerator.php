<?php

namespace Harryes\CrudPackage\Console\Commands;

use Harryes\CrudPackage\Helpers\FileHelper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\NullOutput;

class ControllerGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName): void
    {
        // Generate the resource file
        $this->generateResourceFile($modelName);

        // Generate the controller file using the stub
        $this->generateControllerFile($modelName);
    }

    protected function generateResourceFile($modelName): void
    {
        $resourceName = "{$modelName}Resource";
//        $this->command->call('make:resource', ['name' => "{$resourceName}"], ['quiet' => true]);
        Artisan::call('make:resource', ['name' => $resourceName], new NullOutput());

        $resourceFile = app_path("Http/Resources/{$resourceName}.php");

        if (FileHelper::exists($resourceFile)) {
            $this->updateResourceFile($resourceFile);

            $this->command->info("    Resource [{$resourceFile}] created successfully.");
        } else {
            $this->command->error("    Resource file not found: {$resourceFile}");
        }
    }

    protected function updateResourceFile($resourceFile): void
    {
        $content = FileHelper::read($resourceFile);
        $dynamicResourceNamespace = "\\Harryes\\CrudPackage\\Http\\Resources\\DynamicResource";

        $content = str_replace(
            "return parent::toArray(\$request);",
            "return (new {$dynamicResourceNamespace}(\$this->resource))->toArray(\$request);",
            $content
        );

        FileHelper::write($resourceFile, $content);
    }

    protected function generateControllerFile($modelName): void
    {
        // Get the stub content
        $stubPath = __DIR__ . '/../../stubs/controller.stub';
        $controllerStubContent = FileHelper::read($stubPath);

        // Parse columns
        $columns = $this->command->option('columns');
        $columnsArray = [];
        $fileColumnsNames = [];
        if($columns) {
            $columnsArray = $this->parseColumns($columns);

            // Extract file columns
            $fileColumns = array_filter($columnsArray, function ($column) {
                return Str::startsWith($column['type'], 'file');
            });
            $fileColumnsNames = array_map(function ($column) {
                return $column['name'];
            }, $fileColumns);
        }

        // Prepare dynamic replacements
        $replacements = $this->getReplacements($modelName, $columnsArray, $fileColumnsNames);

        // Replace placeholders in the stub
        $controllerContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $controllerStubContent
        );

        // Write the controller file
        $controllerFilePath = app_path("Http/Controllers/Api/{$modelName}Controller.php");
        $controllerDir = dirname(app_path("Http/Controllers/Api/{$modelName}Controller.php"));
        if (!FileHelper::exists($controllerDir)) {
            FileHelper::makeDirectory($controllerDir, 0755, true);
        }

        FileHelper::write($controllerFilePath, $controllerContent);
        $this->command->info("    Controller [{$controllerFilePath}] created successfully.");
    }

    protected function parseColumns($columns): array
    {
        $columnArray = explode(',', $columns);
        $columnsArray = [];

        foreach ($columnArray as $column) {
            $parts = explode(':', $column);
            $name = $parts[0];
            $type = rtrim($parts[1], '?');
            $nullable = Str::endsWith($parts[1], '?');
            $file = Str::startsWith($parts[1], 'file');
            $columnsArray[] = [
                'name' => $name,
                'type' => $type,
                'nullable' => $nullable,
                'file' => $file
            ];
        }

        return $columnsArray;
    }

    protected function getReplacements($modelName, $columnsArray, $fileColumnsNames): array
    {
        $modelNamespace = "App\\Models\\{$modelName}";
        $resourceNamespace = "App\\Http\\Resources\\{$modelName}Resource";

        return [
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ resourceNamespace }}' => $resourceNamespace,
            '{{ controllerName }}' => "{$modelName}Controller",
            '{{ modelName }}' => $modelName,
            '{{ resourceName }}' => "{$modelName}Resource",
            '{{ storeValidationRules }}' => $this->generateValidationRules($columnsArray, 'store'),
            '{{ updateValidationRules }}' => $this->generateValidationRules($columnsArray, 'update'),
            '{{ fileColumns }}' => implode(', ', $fileColumnsNames),
        ];
    }

    protected function generateValidationRules($columnsArray, $type): string
    {
        $rules = [];
        foreach ($columnsArray as $column) {
            if ($column['file']) {
                continue; // Skip file fields for validation
            }

            if ($type === 'store') {
                $rules[$column['name']] = $column['nullable'] ? 'nullable' : 'required';
            } elseif ($type === 'update') {
                $rules[$column['name']] = $column['nullable'] ? 'nullable' : 'sometimes|required';
            }
        }

        return implode(', ', array_map(function($key) use ($rules) {
            return "'$key' => '" . $rules[$key] . "'";
        }, array_keys($rules)));
    }
}

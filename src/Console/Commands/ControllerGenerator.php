<?php

namespace Harry\CrudPackage\Console\Commands;

use Harry\CrudPackage\Helpers\FileHelper;
use Illuminate\Support\Str;

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
        $this->command->call('make:resource', ['name' => "{$resourceName}"]);

        $resourceFile = app_path("Http/Resources/{$resourceName}.php");

        if (FileHelper::exists($resourceFile)) {
            $this->updateResourceFile($resourceFile, $modelName);
        } else {
            $this->command->error("Resource file not found: {$resourceFile}");
        }
    }

    protected function updateResourceFile($resourceFile, $modelName): void
    {
        $content = FileHelper::read($resourceFile);
        $dynamicResourceNamespace = "\\Harry\\CrudPackage\\Http\\Resources\\DynamicResource";

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
        $stubPath = base_path('stubs/controller.stub');
        $controllerStubContent = FileHelper::read($stubPath);

        // Parse columns
        $columns = $this->command->option('columns');
        $columnsArray = $this->parseColumns($columns);

        // Prepare dynamic replacements
        $replacements = $this->getReplacements($modelName, $columnsArray);

        // Replace placeholders in the stub
        $controllerContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $controllerStubContent
        );

        // Write the controller file
        $controllerFilePath = app_path("Http/Controllers/Api/{$modelName}Controller.php");
        FileHelper::write($controllerFilePath, $controllerContent);
    }

    protected function parseColumns($columns): array
    {
        $columnArray = explode(',', $columns);
        $columnsArray = [];

        foreach ($columnArray as $column) {
            $parts = explode(':', $column);
            $name = $parts[0];
            $type = rtrim($parts[1], '?');
            $nullable = str_ends_with($parts[1], '?');
            $columnsArray[] = ['name' => $name, 'type' => $type, 'nullable' => $nullable];
        }

        return $columnsArray;
    }

    protected function getReplacements($modelName, $columnsArray): array
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
        ];
    }

    protected function generateValidationRules($columnsArray, $type): string
    {
        $rules = [];
        foreach ($columnsArray as $column) {
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

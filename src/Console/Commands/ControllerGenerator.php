<?php

namespace Harry\CrudPackage\Console\Commands;

use Harry\CrudPackage\Helpers\FileHelper;
use Harry\CrudPackage\Helpers\ApiResponse;

class ControllerGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName): void
    {
        $this->command->call('make:controller', [
            'name' => "Api/{$modelName}Controller",
            '--plain' => true,
        ]);

        // Generate the resource file
        $this->generateResourceFile($modelName);

        // Get the controller file path
        $controllerFile = app_path("Http/Controllers/Api/{$modelName}Controller.php");
        $columns = $this->command->option('columns');
        $columnsArray = $this->parseColumns($columns);

        // Update the controller file
        $this->updateControllerFile($controllerFile, $modelName, $columnsArray);
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

    protected function updateControllerFile($controllerFile, $modelName, $columnsArray): void
    {
        $controllerContent = file_get_contents($controllerFile);

        $modelNamespace = "App\\Models\\{$modelName}";
        $resourceNamespace = "App\\Http\\Resources\\{$modelName}Resource";
        $apiResponseNamespace = "Harry\\CrudPackage\\Helpers\\ApiResponse";
        $baseControllerNamespace = "App\\Http\\Controllers\\Api\\BaseController";
        if (!str_contains($controllerContent, $modelNamespace)) {
            $controllerContent = str_replace(
                "namespace App\Http\Controllers\Api;",
                "namespace App\Http\Controllers\Api;\n\nuse {$resourceNamespace};\nuse {$apiResponseNamespace};\nuse {$modelNamespace};\nuse {$baseControllerNamespace};",
                $controllerContent
            );
        }

        $controllerContent = str_replace(
            "extends Controller",
            "extends BaseController",
            $controllerContent
        );

        $validationMethods = [
            'storeValidationRules' => $this->generateValidationRulesMethod($columnsArray, 'store'),
            'updateValidationRules' => $this->generateValidationRulesMethod($columnsArray, 'update'),
        ];

        foreach ($validationMethods as $methodName => $methodContent) {
            $pattern = "/public function {$methodName}\(.*?\{(.*?)\}/s";
            if (preg_match($pattern, $controllerContent)) {
                $controllerContent = preg_replace($pattern, $methodContent, $controllerContent);
            } else {
                $controllerContent .= "\n\n" . $methodContent;
            }
        }

        FileHelper::write($controllerFile, $controllerContent);
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

    protected function replaceOrAddMethod($controllerContent, $methodName, $methodContent): array|string|null
    {
        $pattern = "/public function {$methodName}\(.*?\{(.*?)\}/s";
        if (preg_match($pattern, $controllerContent)) {
            $controllerContent = preg_replace($pattern, $methodContent, $controllerContent);
        } else {
            $controllerContent .= $methodContent;
        }
        return $controllerContent;
    }

    protected function generateValidationRulesMethod($columnsArray, $type): string
    {
        $validationRules = $this->generateValidationRules($columnsArray, $type);
        return "
        public function {$type}ValidationRules(): array
        {
            return [
                $validationRules
            ];
        }
        ";
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

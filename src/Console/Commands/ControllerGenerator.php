<?php

namespace Harry\CrudPackage\Console\Commands;

use Harry\CrudPackage\Helpers\FileHelper;
use Hoa\File\File;

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
            '--api' => true,
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
        if (!str_contains($controllerContent, $modelNamespace)) {
            $controllerContent = str_replace(
                "namespace App\Http\Controllers\Api;",
                "namespace App\Http\Controllers\Api;\n\nuse {$resourceNamespace};\n\nuse {$modelNamespace};",
                $controllerContent
            );
        }

        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'index', $this->generateIndexMethod($modelName));
        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'store', $this->generateStoreMethod($modelName, $columnsArray));
        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'show', $this->generateShowMethod($modelName));
        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'update', $this->generateUpdateMethod($modelName, $columnsArray));
        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'destroy', $this->generateDestroyMethod($modelName));

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

    protected function generateIndexMethod($modelName): string
    {
        $variableName = strtolower($modelName);
        return "
        public function index()
        {
            \${$variableName} = {$modelName}::all();
            return {$modelName}Resource::collection(\${$variableName});
        }
        ";
    }

    protected function generateStoreMethod($modelName, $columnsArray): string
    {
        $validationRules = $this->generateValidationRules($columnsArray);
        $variableName = strtolower($modelName);
        return "
        public function store(Request \$request)
        {
            \$data = \$request->validate([
                $validationRules
            ]);
    
            \${$variableName} = {$modelName}::create(\$data);
            return new {$modelName}Resource(\${$variableName});
        }
        ";
    }

    protected function generateShowMethod($modelName): string
    {
        $variableName = strtolower($modelName);
        return "
        public function show(\$id)
        {
            \${$variableName} = {$modelName}::findOrFail(\$id);
            return new {$modelName}Resource(\${$variableName});
        }
        ";
    }

    protected function generateUpdateMethod($modelName, $columnsArray): string
    {
        $validationRules = $this->generateValidationRules($columnsArray);
        $variableName = strtolower($modelName);
        return "
        public function update(Request \$request, \$id)
        {
            \$data = \$request->validate([
                $validationRules
            ]);
            \${$variableName} = {$modelName}::findOrFail(\$id);
    
            \${$variableName}->update(\$data);
            return new {$modelName}Resource(\${$variableName});
        }
        ";
    }

    protected function generateDestroyMethod($modelName): string
    {
        $variableName = strtolower($modelName);
        return "
        public function destroy(\$id)
        {
            \${$variableName} = {$modelName}::findOrFail(\$id);
            \${$variableName}->delete();
            return response()->json(['message' => '{$modelName} deleted successfully']);
        }
        ";
    }

    protected function generateValidationRules($columnsArray): string
    {
        $rules = [];
        foreach ($columnsArray as $column) {
            $rules[$column['name']] = $column['nullable'] ? 'nullable' : 'required';
        }

        return implode(', ', array_map(function($key) use ($rules) {
            return "'$key' => '" . $rules[$key] . "'";
        }, array_keys($rules)));
    }
}

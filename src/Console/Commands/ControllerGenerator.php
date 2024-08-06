<?php

namespace Harry\CrudPackage\Console\Commands;

use Harry\CrudPackage\Helpers\FileHelper;

class ControllerGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName)
    {
        $this->command->call('make:controller', [
            'name' => "Api/{$modelName}Controller",
            '--api' => true,
        ]);

        $controllerFile = app_path("Http/Controllers/Api/{$modelName}Controller.php");
        $columns = $this->command->option('columns');
        $columnsArray = $this->parseColumns($columns);
        $this->updateControllerFile($controllerFile, $modelName, $columnsArray);
    }

    protected function updateControllerFile($controllerFile, $modelName, $columnsArray)
    {
        $controllerContent = file_get_contents($controllerFile);

        $modelNamespace = "App\\Models\\{$modelName}";
        if (!str_contains($controllerContent, $modelNamespace)) {
            $controllerContent = str_replace(
                "namespace App\Http\Controllers\Api;",
                "namespace App\Http\Controllers\Api;\n\nuse {$modelNamespace};",
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

    protected function parseColumns($columns)
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

    protected function replaceOrAddMethod($controllerContent, $methodName, $methodContent)
    {
        $pattern = "/public function {$methodName}\(.*?\{(.*?)\}/s";
        if (preg_match($pattern, $controllerContent)) {
            $controllerContent = preg_replace($pattern, $methodContent, $controllerContent);
        } else {
            $controllerContent .= $methodContent;
        }
        return $controllerContent;
    }

    protected function generateIndexMethod($modelName)
    {
        return "
        public function index()
        {
            \${$modelName} = {$modelName}::all();
            return response()->json(\${$modelName});
        }
        ";
    }

    protected function generateStoreMethod($modelName, $columnsArray)
    {
        $validationRules = $this->generateValidationRules($columnsArray);
        return "
        public function store(Request \$request)
        {
            \$data = \$request->validate([
                $validationRules
            ]);
    
            \${$modelName} = {$modelName}::create(\$data);
            return response()->json(\${$modelName}, 201);
        }
        ";
    }

    protected function generateShowMethod($modelName)
    {
        return "
        public function show(\$id)
        {
            \${$modelName} = {$modelName}::findOrFail(\$id);
            return response()->json(\${$modelName});
        }
        ";
    }

    protected function generateUpdateMethod($modelName, $columnsArray)
    {
        $validationRules = $this->generateValidationRules($columnsArray);
        return "
        public function update(Request \$request, \$id)
        {
            \${$modelName} = {$modelName}::findOrFail(\$id);
            \$data = \$request->validate([
                $validationRules
            ]);
    
            \${$modelName}->update(\$data);
            return response()->json(\${$modelName});
        }
        ";
    }

    protected function generateDestroyMethod($modelName)
    {
        return "
        public function destroy(\$id)
        {
            \${$modelName} = {$modelName}::findOrFail(\$id);
            \${$modelName}->delete();
            return response()->json(['message' => '{$modelName} deleted successfully']);
        }
        ";
    }

    protected function generateValidationRules($columnsArray)
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

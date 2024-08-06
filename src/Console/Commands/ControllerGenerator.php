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
        $this->updateControllerFile($controllerFile, $modelName);
    }

    protected function updateControllerFile($controllerFile, $modelName)
    {
        $controllerContent = file_get_contents($controllerFile);

        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'index', $this->generateIndexMethod($modelName));
        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'store', $this->generateStoreMethod($modelName));
        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'show', $this->generateShowMethod($modelName));
        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'update', $this->generateUpdateMethod($modelName));
        $controllerContent = $this->replaceOrAddMethod($controllerContent, 'destroy', $this->generateDestroyMethod($modelName));

        FileHelper::write($controllerFile, $controllerContent);
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

    protected function generateStoreMethod($modelName)
    {
        return "
    public function store(Request \$request)
    {
        \$data = \$request->validate([
            // Add validation rules here
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

    protected function generateUpdateMethod($modelName)
    {
        return "
    public function update(Request \$request, \$id)
    {
        \${$modelName} = {$modelName}::findOrFail(\$id);
        \$data = \$request->validate([
            // Add validation rules here
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
}

<?php
namespace Harry\CrudPackage\Commands;

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
            'name' => "{$modelName}Controller",
            '--api' => true,
        ]);

        $controllerFile = app_path("Http/Controllers/Api/{$modelName}Controller.php");
        $this->updateControllerFile($controllerFile, $modelName);
    }

    protected function updateControllerFile($controllerFile, $modelName)
    {
        $controllerContent = file_get_contents($controllerFile);

        $indexMethod = $this->generateIndexMethod($modelName);
        $storeMethod = $this->generateStoreMethod($modelName);
        $showMethod = $this->generateShowMethod($modelName);
        $updateMethod = $this->generateUpdateMethod($modelName);
        $destroyMethod = $this->generateDestroyMethod($modelName);

        $controllerContent = str_replace(
            'use App\Http\Controllers\Api\Controller;',
            "use App\Http\Controllers\Api\Controller;\nuse App\Models\\{$modelName};",
            $controllerContent
        );

        $controllerContent = str_replace(
            'public function index()',
            $indexMethod,
            $controllerContent
        );

        $controllerContent .= $storeMethod;
        $controllerContent .= $showMethod;
        $controllerContent .= $updateMethod;
        $controllerContent .= $destroyMethod;

        FileHelper::write($controllerFile, $controllerContent);
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

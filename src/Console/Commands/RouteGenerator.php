<?php

namespace harry\CrudPackage\Commands;

use Illuminate\Support\Str;

class RouteGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName)
    {
        $route = "Route::apiResource('".strtolower(Str::plural($modelName))."', '{$modelName}Controller');";
        file_put_contents(base_path('routes/api.php'), $route.PHP_EOL, FILE_APPEND);
    }
}

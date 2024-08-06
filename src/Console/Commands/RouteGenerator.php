<?php

namespace harry\CrudPackage\Commands;

class RouteGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName)
    {
        $route = "Route::apiResource('".strtolower(str_plural($modelName))."', '{$modelName}Controller');";
        file_put_contents(base_path('routes/api.php'), $route.PHP_EOL, FILE_APPEND);
    }
}

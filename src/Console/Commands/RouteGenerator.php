<?php

namespace Harryes\CrudPackage\Console\Commands;

use Illuminate\Support\Str;

class RouteGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName): void
    {
        $controllerNamespace = "App\\Http\\Controllers\\Api\\{$modelName}Controller";
        $route = "Route::apiResource('".Str::plural(str_replace('_', '-', Str::snake($modelName)))."', '{$controllerNamespace}');";
        file_put_contents(base_path('routes/api.php'), $route.PHP_EOL, FILE_APPEND);
    }
}

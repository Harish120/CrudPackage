<?php

namespace Harry\CrudPackage\Console\Commands;

use Harry\CrudPackage\Helpers\FileHelper;
use Illuminate\Support\Facades\File;

class ModelGenerator
{
    protected $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function generate($modelName, $columns)
    {
        $this->command->call('make:model', [
            'name' => $modelName
        ]);

        $modelFile = app_path("Models/{$modelName}.php");
        if (File::exists($modelFile)) {
            $this->updateModelFile($modelFile, $columns);
        } else {
            $this->command->error("Model file not found: {$modelFile}");
        }
    }

    protected function updateModelFile($modelFile, $columns)
    {
        $fillable = $this->generateFillable($columns);

        $content = FileHelper::read($modelFile);
        $content = str_replace('use HasFactory;', "use HasFactory;\n\n    protected \$fillable = {$fillable};", $content);

        FileHelper::write($modelFile, $content);
    }

    protected function generateFillable($columns)
    {
        $columnsArray = explode(',', $columns);
        $fillableArray = array_map(function ($column) {
            return "'{$column}'";
        }, $columnsArray);

        return '[' . implode(', ', $fillableArray) . ']';
    }
}

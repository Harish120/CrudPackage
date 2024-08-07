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

    public function generate($modelName, $columns): void
    {
        $this->command->call('make:model', [
            'name' => $modelName,
        ]);

        $modelFile = app_path("Models/{$modelName}.php");

        if (File::exists($modelFile)) {
            $this->updateModelFile($modelFile, $columns);
        } else {
            $this->command->error("Model file not found: {$modelFile}");
        }
    }

    protected function updateModelFile($modelFile, $columns): void
    {
        $fillable = $this->generateFillable($columns);

        $content = File::get($modelFile);
        $content = str_replace('use HasFactory;', "use HasFactory;\n\n    protected \$fillable = {$fillable};", $content);

        File::put($modelFile, $content);
    }

    protected function generateFillable($columns): string
    {
        $columnsArray = explode(',', $columns);
        $fillableArray = array_map(function ($column) {
            // Remove anything after ':' if it exists
            return "'" . explode(':', $column)[0] . "'";
        }, $columnsArray);

        return '[' . implode(', ', $fillableArray) . ']';
    }
}

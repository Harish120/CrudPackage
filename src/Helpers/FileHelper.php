<?php

namespace Harryes\CrudPackage\Helpers;

use Illuminate\Support\Facades\File;

class FileHelper
{
    /**
     * Write content to a file, with an option to append.
     *
     * @param string $path The path to the file.
     * @param string $content The content to write.
     * @param bool $append Whether to append the content.
     * @return void
     */
    public static function write($path, $content, $append = false)
    {
        if ($append) {
            File::append($path, $content);
        } else {
            File::put($path, $content);
        }
    }

    /**
     * Read content from a file.
     *
     * @param string $path The path to the file.
     * @return string The file content.
     */
    public static function read($path): string
    {
        return File::get($path);
    }

    /**
     * Check if a file exists.
     *
     * @param string $path The path to the file.
     * @return bool
     */
    public static function exists($path): bool
    {
        return File::exists($path);
    }

    // make directory
    public static function makeDirectory($path, $mode, $recursive = true, $force = false): bool
    {
        return File::makeDirectory($path, $mode, $recursive = true, $force = false);
    }
}

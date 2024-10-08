<?php

namespace Orchestra\Testbench\Bootstrap;

use Dotenv\Dotenv;
use Orchestra\Testbench\Foundation\Env;

/**
 * @internal
 */
final class LoadEnvironmentVariables extends \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables
{
    /**
     * Create a Dotenv instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return \Dotenv\Dotenv
     */
    protected function createDotenv($app)
    {
        /** @phpstan-ignore method.notFound, method.notFound */
        if (! file_exists(implode('/', [$app->environmentPath(), $app->environmentFile()]))) {
            return Dotenv::create(
                Env::getRepository(), (string) realpath(__DIR__.'/stubs'), '.env.testbench'
            );
        }

        return parent::createDotenv($app);
    }
}

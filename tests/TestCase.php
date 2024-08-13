<?php
namespace Harryes\CrudPackage\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Harryes\CrudPackage\CrudPackageServiceProvider;
use Dotenv\Dotenv;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            CrudPackageServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        if (file_exists(__DIR__.'/../.env.testing')) {
            Dotenv::createImmutable(__DIR__.'/../', '.env.testing')->load();
        }

        $app['config']->set('database.default', env('DB_CONNECTION', 'mysql'));
        $app['config']->set('database.connections.mysql', [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'crud_package'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', 'password'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ]);
    }
}

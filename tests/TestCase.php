<?php

namespace Sslcommerz\Laravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Sslcommerz\Laravel\SslcommerzServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SslcommerzServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'SSLCOMMERZ' => \Sslcommerz\Laravel\Facades\SSLCOMMERZ::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sslcommerz.sandbox', true);
        $app['config']->set('sslcommerz.store_id', 'testbox');
        $app['config']->set('sslcommerz.store_password', 'qwerty');
        $app['config']->set('sslcommerz.logging.enabled', false);
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}

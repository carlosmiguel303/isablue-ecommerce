<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        // Check before RefreshDatabase can run any migration.
        if ($app['config']->get('database.default') !== 'mysql' ||
            $app['config']->get('database.connections.mysql.database') !== 'isablue_2026_test') {
            throw new \RuntimeException('Las pruebas solo pueden usar isablue_2026_test. Limpia la configuración cacheada.');
        }

        return $app;
    }
}

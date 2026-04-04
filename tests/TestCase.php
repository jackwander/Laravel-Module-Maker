<?php

namespace Jackwander\ModuleMaker\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Jackwander\ModuleMaker\ModuleServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ModuleServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}

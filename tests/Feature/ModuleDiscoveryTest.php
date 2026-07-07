<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\ModuleServiceProvider;
use Jackwander\ModuleMaker\Tests\TestCase;

class ModuleDiscoveryTest extends TestCase
{
    /**
     * Force the production discovery path (cached via the cache service) and
     * ensure the modules directory exists before the provider boots.
     */
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['env'] = 'production';

        File::ensureDirectoryExists($app['path'] . '/Modules');
    }

    protected function tearDown(): void
    {
        Cache::forget('module-maker.modules');
        File::deleteDirectory(app_path('Modules'));

        parent::tearDown();
    }

    public function test_boots_outside_local_and_caches_module_list()
    {
        // Deferred discovery runs during boot, so the module list is cached
        // forever outside local/testing.
        $this->assertTrue($this->app->environment('production'));
        $this->assertTrue(Cache::has('module-maker.modules'));
        $this->assertSame([], Cache::get('module-maker.modules'));
    }

    public function test_register_does_not_resolve_the_cache_service()
    {
        // Regression: discovery ran inside register() and called cache() before
        // the framework had bound the cache service, throwing
        // "Target class [cache] does not exist". Unbinding cache reproduces that
        // window: with discovery deferred to the booting phase, register() must
        // not touch the cache at all.
        $app = $this->app;
        $cache = $app['cache'];
        unset($app['cache'], $app['cache.store']);
        $this->assertFalse($app->bound('cache'));

        (new ModuleServiceProvider($app))->register();

        // register() only queued a booting callback — cache stays untouched.
        $this->assertFalse($app->bound('cache'));

        $app->instance('cache', $cache); // restore for teardown
    }
}

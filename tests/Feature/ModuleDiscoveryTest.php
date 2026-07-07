<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\ModuleServiceProvider;
use Jackwander\ModuleMaker\Tests\TestCase;

class ModuleDiscoveryTest extends TestCase
{
    /**
     * Reproduce the deploy-time environment that crashed boot: a production app
     * whose cache is backed by the database, with no `cache` table present.
     * Module discovery must not read the cache/DB, so boot must not query it.
     */
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['env'] = 'production';
        $app['config']->set('cache.default', 'database');

        File::ensureDirectoryExists($app['path'] . '/Modules');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Modules'));

        parent::tearDown();
    }

    public function test_boots_in_production_under_database_cache_without_a_cache_table()
    {
        // Reaching this assertion means boot did not throw a QueryException from
        // discovery hitting a database-backed cache table that does not exist.
        $this->assertTrue($this->app->environment('production'));
    }

    public function test_discovers_modules_from_the_filesystem()
    {
        File::ensureDirectoryExists(app_path('Modules/Alpha'));
        File::ensureDirectoryExists(app_path('Modules/Beta'));

        $method = new \ReflectionMethod(ModuleServiceProvider::class, 'discoverModules');
        $method->setAccessible(true);
        $modules = $method->invoke(new ModuleServiceProvider($this->app));

        sort($modules);
        $this->assertSame(['Alpha', 'Beta'], $modules);
    }

    public function test_register_never_resolves_the_cache_service()
    {
        // Guard the invariant directly: with the cache service removed,
        // register() (which registers module providers) must not resolve it —
        // discovery is purely filesystem-based.
        $app = $this->app;
        File::ensureDirectoryExists(app_path('Modules/Gamma'));
        unset($app['cache'], $app['cache.store']);
        $this->assertFalse($app->bound('cache'));

        (new ModuleServiceProvider($app))->register();

        $this->assertFalse($app->bound('cache'));
    }
}

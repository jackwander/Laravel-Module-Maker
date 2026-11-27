<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\ModuleServiceProvider;
use Jackwander\ModuleMaker\ModuleStatus;
use Jackwander\ModuleMaker\Tests\TestCase;

class ModuleStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Modules'));
        File::delete(base_path('modules_statuses.json'));

        parent::tearDown();
    }

    /**
     * Create a module directory with a route file, and optionally a provider
     * class that binds "{name}.provider" when registered.
     */
    protected function makeModule(string $name, bool $withProvider = false): void
    {
        $path = app_path("Modules/{$name}");
        $slug = strtolower($name);

        File::ensureDirectoryExists("{$path}/Routes");
        File::put("{$path}/Routes/api.php", "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('{$slug}-ping', fn () => '{$slug}');\n");

        if ($withProvider) {
            File::ensureDirectoryExists("{$path}/Providers");
            $file = "{$path}/Providers/{$name}ServiceProvider.php";
            File::put($file, "<?php\n\nnamespace App\\Modules\\{$name}\\Providers;\n\nclass {$name}ServiceProvider extends \\Illuminate\\Support\\ServiceProvider\n{\n    public function register(): void\n    {\n        \$this->app->instance('{$slug}.provider', true);\n    }\n}\n");
            require_once $file;
        }
    }

    protected function writeStatuses(string $json): void
    {
        File::put(base_path('modules_statuses.json'), $json);
    }

    /** Modules and status file must exist before boot, so rebuild the app. */
    protected function reboot(): void
    {
        $this->refreshApplication();
    }

    public function test_without_a_status_file_every_module_loads()
    {
        $this->makeModule('Alpha');
        $this->makeModule('Beta');
        $this->reboot();

        $this->get('/api/v1/alpha-ping')->assertOk();
        $this->get('/api/v1/beta-ping')->assertOk();
    }

    public function test_disabled_module_route_is_not_registered()
    {
        $this->makeModule('Alpha');
        $this->makeModule('Beta');
        $this->writeStatuses('{"Alpha": false, "Beta": true}');
        $this->reboot();

        $this->get('/api/v1/alpha-ping')->assertNotFound();
        $this->get('/api/v1/beta-ping')->assertOk();
    }

    public function test_disabled_module_provider_is_not_registered()
    {
        $this->makeModule('StatusOn', withProvider: true);
        $this->makeModule('StatusOff', withProvider: true);
        $this->writeStatuses('{"StatusOff": false}');
        $this->reboot();

        $this->assertTrue($this->app->bound('statuson.provider'));
        $this->assertFalse($this->app->bound('statusoff.provider'));
    }

    public function test_invalid_json_enables_everything()
    {
        $this->makeModule('Alpha');
        $this->writeStatuses('{"Alpha": false,,, nope');
        $this->reboot();

        $this->get('/api/v1/alpha-ping')->assertOk();
    }

    public function test_core_cannot_be_disabled_by_command()
    {
        $this->makeModule('Core');

        $this->artisan('jw:module:disable', ['name' => 'Core'])
            ->expectsOutputToContain('cannot be disabled')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist(base_path('modules_statuses.json'));
    }

    public function test_hand_edited_core_false_is_ignored()
    {
        $this->makeModule('Core');
        $this->writeStatuses('{"Core": false}');
        $this->reboot();

        $this->get('/api/v1/core-ping')->assertOk();
        $this->assertTrue(ModuleStatus::isEnabled('Core'));
    }

    public function test_disable_then_enable_round_trips()
    {
        $this->makeModule('Alpha');

        $this->artisan('jw:module:disable', ['name' => 'Alpha'])
            ->expectsOutputToContain('Module Alpha disabled.')
            ->expectsOutputToContain('optimize:clear')
            ->assertExitCode(0);
        $this->assertSame(['Alpha' => false], json_decode(File::get(base_path('modules_statuses.json')), true));

        // Idempotent.
        $this->artisan('jw:module:disable', ['name' => 'Alpha'])
            ->expectsOutputToContain('already disabled')
            ->assertExitCode(0);

        $this->artisan('jw:module:enable', ['name' => 'Alpha'])
            ->expectsOutputToContain('Module Alpha enabled.')
            ->assertExitCode(0);
        $this->assertSame(['Alpha' => true], json_decode(File::get(base_path('modules_statuses.json')), true));

        $this->reboot();
        $this->get('/api/v1/alpha-ping')->assertOk();
    }

    public function test_status_file_is_pretty_printed_with_sorted_keys()
    {
        $this->makeModule('Zeta');
        $this->makeModule('Alpha');

        Artisan::call('jw:module:disable', ['name' => 'Zeta']);
        Artisan::call('jw:module:disable', ['name' => 'Alpha']);

        $this->assertSame("{\n    \"Alpha\": false,\n    \"Zeta\": false\n}\n", File::get(base_path('modules_statuses.json')));
    }

    public function test_unknown_module_errors()
    {
        $this->artisan('jw:module:disable', ['name' => 'Ghost'])
            ->expectsOutputToContain('Ghost not found.')
            ->assertExitCode(1);
        $this->artisan('jw:module:enable', ['name' => 'Ghost'])
            ->expectsOutputToContain('Ghost not found.')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist(base_path('modules_statuses.json'));
    }

    public function test_commands_refuse_to_overwrite_an_invalid_status_file()
    {
        $this->makeModule('Alpha');
        $this->writeStatuses('{broken');

        $this->artisan('jw:module:disable', ['name' => 'Alpha'])
            ->expectsOutputToContain('not valid JSON')
            ->assertExitCode(1);

        $this->assertSame('{broken', File::get(base_path('modules_statuses.json')));
    }

    public function test_list_shows_both_states()
    {
        $this->makeModule('Alpha');
        $this->makeModule('Beta');
        $this->writeStatuses('{"Beta": false}');

        $this->artisan('jw:module:list')
            ->expectsTable(['Module', 'Status'], [['Alpha', 'enabled'], ['Beta', 'disabled']])
            ->assertExitCode(0);
    }

    public function test_check_reports_disabled_modules_as_disabled()
    {
        $this->makeModule('Beta');
        $this->writeStatuses('{"Beta": false}');

        $this->artisan('jw:check')
            ->expectsOutputToContain('Disabled')
            ->assertExitCode(0);
    }

    public function test_ai_context_marks_disabled_modules()
    {
        $this->makeModule('Alpha');
        $this->makeModule('Beta');
        $this->writeStatuses('{"Beta": false}');

        $snapshot = app(\Jackwander\ModuleMaker\AI\Inspector::class)->inspect();
        $this->assertSame(['Alpha', 'Beta'], array_keys($snapshot->modules));
        $this->assertSame(['Beta'], $snapshot->disabledModules);

        $section = (new \Jackwander\ModuleMaker\AI\Sections\ModulesSection())
            ->render($snapshot, \Jackwander\ModuleMaker\AI\Support\Depth::Full);
        $this->assertStringContainsString('| Beta (disabled) |', $section);
        $this->assertStringContainsString('| Alpha |', $section);

        $tool = app(\Jackwander\ModuleMaker\AI\Mcp\Tools\ListModules::class)->handle([]);
        $this->assertStringContainsString('Disabled modules', $tool);
        $this->assertStringContainsString('Beta', $tool);
    }

    public function test_status_path_is_configurable()
    {
        $custom = base_path('custom_statuses.json');
        config()->set('module-maker.paths.statuses', $custom);
        $this->makeModule('Alpha');

        try {
            Artisan::call('jw:module:disable', ['name' => 'Alpha']);
            $this->assertSame(['Alpha' => false], json_decode(File::get($custom), true));
            $this->assertFileDoesNotExist(base_path('modules_statuses.json'));
        } finally {
            File::delete($custom);
        }
    }

    public function test_discovery_with_a_status_file_touches_neither_cache_nor_database()
    {
        $app = $this->app;
        $this->makeModule('Alpha');
        $this->makeModule('Beta');
        $this->writeStatuses('{"Alpha": false}');
        unset($app['cache'], $app['cache.store'], $app['db']);

        $provider = new ModuleServiceProvider($app);
        $provider->register();

        $method = new \ReflectionMethod(ModuleServiceProvider::class, 'discoverModules');
        $method->setAccessible(true);

        $this->assertSame(['Beta'], $method->invoke($provider));
        $this->assertFalse($app->bound('cache'));
        $this->assertFalse($app->bound('db'));
    }
}

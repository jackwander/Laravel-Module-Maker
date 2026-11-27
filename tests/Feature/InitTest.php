<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\Tests\TestCase;

class InitTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(config_path('module-maker.php'));
        File::deleteDirectory(app_path('Modules'));
        File::deleteDirectory(base_path('custom'));

        parent::tearDown();
    }

    protected function fixture(string $name): string
    {
        return File::get(dirname(__DIR__) . "/Fixtures/{$name}.php.txt");
    }

    /** A host config with custom values, still pointing at the vendor base classes. */
    protected function hostConfig(string $serviceLine = "'service'        => \\Jackwander\\ModuleMaker\\Base\\BaseService::class,"): string
    {
        return <<<PHP
<?php

return [
    // Host comment that must survive.
    'paths' => [
        'modules' => base_path('custom/Domains'),
        'api_prefix' => 'api/v9',
    ],

    'namespaces' => [
        'root' => 'Acme\\\\Domains',
    ],

    'base_classes' => [
        {$serviceLine}
        'api_controller' => Jackwander\\ModuleMaker\\Base\\BaseApiController::class,
        'model'          => \\Jackwander\\ModuleMaker\\Base\\BaseModel::class,
    ],

    'host_extra' => ['keep' => true],
];

PHP;
    }

    /** Mirror what the host config sets, as Laravel would load it at boot. */
    protected function useHostRuntimeConfig(): void
    {
        config()->set('module-maker.paths.modules', base_path('custom/Domains'));
        config()->set('module-maker.namespaces.root', 'Acme\\Domains');
    }

    public function test_fresh_app_output_is_unchanged()
    {
        $this->assertFileDoesNotExist(config_path('module-maker.php'));

        $this->assertSame(0, Artisan::call('jw:init'));

        $this->assertSame($this->fixture('init-config'), File::get(config_path('module-maker.php')));

        foreach (['BaseModel', 'BaseApiController', 'BaseService'] as $class) {
            $this->assertSame(
                $this->fixture("init-core-{$class}"),
                File::get(app_path("Modules/Core/{$class}.php"))
            );
        }
    }

    public function test_existing_config_keeps_everything_but_the_three_base_class_lines()
    {
        $original = $this->hostConfig();
        File::put(config_path('module-maker.php'), $original);
        $this->useHostRuntimeConfig();

        $this->assertSame(0, Artisan::call('jw:init'));

        $expected = str_replace(
            [
                '\\Jackwander\\ModuleMaker\\Base\\BaseService::class',
                'Jackwander\\ModuleMaker\\Base\\BaseApiController::class',
                '\\Jackwander\\ModuleMaker\\Base\\BaseModel::class',
            ],
            [
                '\\Acme\\Domains\\Core\\BaseService::class',
                'Acme\\Domains\\Core\\BaseApiController::class',
                '\\Acme\\Domains\\Core\\BaseModel::class',
            ],
            $original
        );

        $this->assertSame($expected, File::get(config_path('module-maker.php')));
        $this->assertStringContainsString('Localized', Artisan::output());
    }

    public function test_custom_root_puts_core_under_configured_path_and_namespace()
    {
        File::put(config_path('module-maker.php'), $this->hostConfig());
        $this->useHostRuntimeConfig();

        Artisan::call('jw:init');

        foreach (['BaseModel', 'BaseApiController', 'BaseService'] as $class) {
            $file = base_path("custom/Domains/Core/{$class}.php");
            $this->assertFileExists($file);
            $this->assertStringContainsString('namespace Acme\\Domains\\Core;', File::get($file));
        }

        $this->assertDirectoryDoesNotExist(app_path('Modules/Core'));

        $config = require config_path('module-maker.php');
        $this->assertSame([
            'service' => 'Acme\\Domains\\Core\\BaseService',
            'api_controller' => 'Acme\\Domains\\Core\\BaseApiController',
            'model' => 'Acme\\Domains\\Core\\BaseModel',
        ], $config['base_classes']);
        $this->assertSame(base_path('custom/Domains'), $config['paths']['modules']);
        $this->assertSame('api/v9', $config['paths']['api_prefix']);
        $this->assertSame(['keep' => true], $config['host_extra']);
    }

    public function test_customised_base_class_is_left_alone_with_a_paste_hint()
    {
        $original = $this->hostConfig("'service'        => \\Acme\\Shared\\MyService::class,");
        File::put(config_path('module-maker.php'), $original);
        $this->useHostRuntimeConfig();

        Artisan::call('jw:init');
        $output = Artisan::output();

        $written = File::get(config_path('module-maker.php'));
        $this->assertStringContainsString("'service'        => \\Acme\\Shared\\MyService::class,", $written);
        $this->assertStringContainsString('\\Acme\\Domains\\Core\\BaseModel::class', $written);
        $this->assertStringContainsString("'service' => \\Acme\\Domains\\Core\\BaseService::class,", $output);
    }

    public function test_second_run_changes_nothing()
    {
        File::put(config_path('module-maker.php'), $this->hostConfig());
        $this->useHostRuntimeConfig();

        Artisan::call('jw:init');
        $config = File::get(config_path('module-maker.php'));
        $core = File::get(base_path('custom/Domains/Core/BaseService.php'));

        Artisan::call('jw:init');
        $output = Artisan::output();

        $this->assertSame($config, File::get(config_path('module-maker.php')));
        $this->assertSame($core, File::get(base_path('custom/Domains/Core/BaseService.php')));
        $this->assertStringNotContainsString('Localized', $output);
        $this->assertStringContainsString('Skipped', $output);
    }

    public function test_second_run_on_fresh_app_changes_nothing()
    {
        Artisan::call('jw:init');
        $config = File::get(config_path('module-maker.php'));

        Artisan::call('jw:init');

        $this->assertSame($config, File::get(config_path('module-maker.php')));
        $this->assertStringNotContainsString('Localized', Artisan::output());
    }

    public function test_published_core_stub_without_placeholder_still_works()
    {
        $published = base_path('stubs/vendor/module-maker/core-model.stub');
        File::ensureDirectoryExists(dirname($published));
        File::put($published, "<?php\n\nnamespace App\\Modules\\Core;\n\nclass BaseModel {}\n");

        try {
            Artisan::call('jw:init');
            $this->assertSame(File::get($published), File::get(app_path('Modules/Core/BaseModel.php')));
        } finally {
            File::deleteDirectory(base_path('stubs'));
        }
    }
}

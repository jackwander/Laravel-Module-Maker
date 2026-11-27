<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\Tests\TestCase;

/**
 * Regression: several commands hardcoded app_path('Modules') / "app/Modules" /
 * App\Modules and ignored module-maker.paths.modules and namespaces.root.
 */
class ConfiguredModulesPathTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('custom'));
        File::deleteDirectory(app_path('Modules'));

        parent::tearDown();
    }

    protected function useCustomLocation(): string
    {
        $path = base_path('custom/Domains');
        config()->set('module-maker.paths.modules', $path);
        config()->set('module-maker.namespaces.root', 'Acme\\Domains');

        return $path;
    }

    public function test_check_honours_configured_path_and_namespace()
    {
        $path = $this->useCustomLocation();
        File::ensureDirectoryExists("{$path}/Billing/Database/Seeders");
        File::put("{$path}/Billing/Database/Seeders/InvoiceSeeder.php", '<?php');

        $this->assertSame(0, Artisan::call('jw:check'));
        $output = Artisan::output();

        $this->assertMatchesRegularExpression('/Modules Directory \.+ Found/', $output);
        $this->assertMatchesRegularExpression('/Billing \.+ No Provider Found/', $output);
        $this->assertStringContainsString('\\Acme\\Domains\\Billing\\Database\\Seeders\\InvoiceSeeder::class', $output);
    }

    public function test_check_default_config_unchanged()
    {
        File::ensureDirectoryExists(app_path('Modules/Billing/Database/Seeders'));
        File::put(app_path('Modules/Billing/Database/Seeders/InvoiceSeeder.php'), '<?php');

        $this->assertSame(0, Artisan::call('jw:check'));
        $output = Artisan::output();

        $this->assertMatchesRegularExpression('/Modules Directory \.+ Found/', $output);
        $this->assertMatchesRegularExpression('/Billing \.+ No Provider Found/', $output);
        $this->assertStringContainsString('\\App\\Modules\\Billing\\Database\\Seeders\\InvoiceSeeder::class', $output);
    }

    public function test_make_seeder_honours_configured_path_and_namespace()
    {
        $path = $this->useCustomLocation();
        File::ensureDirectoryExists("{$path}/Billing");

        $this->artisan('jw:make-seeder', ['name' => 'Invoice', '--module' => 'Billing'])
            ->expectsOutputToContain('\\Acme\\Domains\\Billing\\Database\\Seeders\\InvoiceSeeder::class')
            ->assertExitCode(0);

        $file = "{$path}/Billing/Database/Seeders/InvoiceSeeder.php";
        $this->assertFileExists($file);
        $this->assertStringContainsString('namespace Acme\\Domains\\Billing\\Database\\Seeders;', File::get($file));
        $this->assertDirectoryDoesNotExist(app_path('Modules'));
    }

    public function test_make_seeder_default_config_unchanged()
    {
        File::ensureDirectoryExists(app_path('Modules/Billing'));

        $this->artisan('jw:make-seeder', ['name' => 'Invoice', '--module' => 'Billing'])->assertExitCode(0);

        $file = app_path('Modules/Billing/Database/Seeders/InvoiceSeeder.php');
        $this->assertFileExists($file);
        $this->assertStringContainsString('namespace App\\Modules\\Billing\\Database\\Seeders;', File::get($file));
    }

    public function test_make_factory_honours_configured_path_and_namespace()
    {
        $path = $this->useCustomLocation();
        File::ensureDirectoryExists("{$path}/Billing");

        $this->artisan('jw:make-factory', ['name' => 'Invoice', '--module' => 'Billing'])->assertExitCode(0);

        $file = "{$path}/Billing/Database/Factories/InvoiceFactory.php";
        $this->assertFileExists($file);
        $content = File::get($file);
        $this->assertStringContainsString('namespace Acme\\Domains\\Billing\\Database\\Factories;', $content);
        $this->assertStringContainsString('use Acme\\Domains\\Billing\\Models\\Invoice;', $content);
        $this->assertDirectoryDoesNotExist(app_path('Modules'));
    }

    public function test_make_factory_default_config_unchanged()
    {
        File::ensureDirectoryExists(app_path('Modules/Billing'));

        $this->artisan('jw:make-factory', ['name' => 'Invoice', '--module' => 'Billing'])->assertExitCode(0);

        $content = File::get(app_path('Modules/Billing/Database/Factories/InvoiceFactory.php'));
        $this->assertStringContainsString('namespace App\\Modules\\Billing\\Database\\Factories;', $content);
        $this->assertStringContainsString('use App\\Modules\\Billing\\Models\\Invoice;', $content);
    }

    public function test_make_migration_requires_module_flag()
    {
        $this->artisan('jw:make-migration', ['name' => 'create_invoices_table', '--create' => 'invoices'])
            ->expectsOutputToContain('The --module flag is required.')
            ->assertExitCode(1);
    }

    public function test_make_seeder_rejects_unknown_module()
    {
        $this->artisan('jw:make-seeder', ['name' => 'Invoice', '--module' => 'Ghost'])
            ->expectsOutputToContain('Ghost not found.')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist(app_path('Modules/Ghost'));
    }

    public function test_make_factory_rejects_unknown_module()
    {
        $this->artisan('jw:make-factory', ['name' => 'Invoice', '--module' => 'Ghost'])
            ->expectsOutputToContain('Ghost not found.')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist(app_path('Modules/Ghost'));
    }

    public function test_seeder_and_factory_work_on_a_freshly_made_module()
    {
        Artisan::call('jw:make-module', ['name' => 'Billing']);

        $this->assertSame(0, Artisan::call('jw:make-seeder', ['name' => 'Invoice', '--module' => 'Billing']));
        $this->assertSame(0, Artisan::call('jw:make-factory', ['name' => 'Invoice', '--module' => 'Billing']));
        $this->assertFileExists(app_path('Modules/Billing/Database/Seeders/InvoiceSeeder.php'));
        $this->assertFileExists(app_path('Modules/Billing/Database/Factories/InvoiceFactory.php'));
    }

    public function test_make_migration_honours_configured_path()
    {
        $path = $this->useCustomLocation();
        File::ensureDirectoryExists("{$path}/Billing/Database/Migrations");

        $this->artisan('jw:make-migration', ['name' => 'create_invoices_table', '--module' => 'Billing', '--create' => 'invoices'])
            ->assertExitCode(0);

        $this->assertCount(1, File::files("{$path}/Billing/Database/Migrations"));
    }

    public function test_make_migration_default_config_unchanged()
    {
        File::ensureDirectoryExists(app_path('Modules/Billing/Database/Migrations'));

        $this->artisan('jw:make-migration', ['name' => 'create_invoices_table', '--module' => 'Billing', '--create' => 'invoices'])
            ->assertExitCode(0);

        $this->assertCount(1, File::files(app_path('Modules/Billing/Database/Migrations')));
    }

    public function test_factory_name_guessing_honours_configured_namespace()
    {
        $this->useCustomLocation();

        $this->assertSame(
            'Acme\\Domains\\Billing\\Database\\Factories\\InvoiceFactory',
            Factory::resolveFactoryName('Acme\\Domains\\Billing\\Models\\Invoice')
        );
    }

    public function test_factory_name_guessing_default_config_unchanged()
    {
        $this->assertSame(
            'App\\Modules\\Billing\\Database\\Factories\\InvoiceFactory',
            Factory::resolveFactoryName('App\\Modules\\Billing\\Models\\Invoice')
        );
        $this->assertSame(
            'Database\\Factories\\UserFactory',
            Factory::resolveFactoryName('App\\Models\\User')
        );
    }

    /** Every AI text that names the Core bridge, plus the catalog's jw:init entry. */
    protected function coreMentions(): string
    {
        $snapshot = app(\Jackwander\ModuleMaker\AI\Inspector::class)->inspect();
        $snapshot->bridgeActive = true;
        $section = new \Jackwander\ModuleMaker\AI\Sections\ArchitectureSection();
        $builder = app(\Jackwander\ModuleMaker\AI\ContextBuilder::class);
        $rules = new \Jackwander\ModuleMaker\AI\Sections\AiRulesSection();

        Artisan::call('jw:ai:init', ['--platforms' => 'cursor', '--no-mcp' => true]);
        $cursor = File::get(base_path('.cursor/rules/module-maker.mdc'));
        File::deleteDirectory(base_path('.cursor'));
        File::deleteDirectory(base_path('.ai'));
        File::delete(config_path('module-ai.php'));

        return implode("\n", [
            $section->render($snapshot, \Jackwander\ModuleMaker\AI\Support\Depth::Full),
            $section->render($snapshot, \Jackwander\ModuleMaker\AI\Support\Depth::Compressed),
            $builder->buildSummary($snapshot),
            $rules->render($snapshot, \Jackwander\ModuleMaker\AI\Support\Depth::Full),
            $cursor,
            implode(', ', app(\Jackwander\ModuleMaker\AI\CommandCatalog::class)->find('jw:init')['creates']),
        ]);
    }

    public function test_ai_context_names_the_configured_core_namespace()
    {
        $this->useCustomLocation();

        $text = $this->coreMentions();

        $this->assertStringNotContainsString('App\\Modules', $text);
        $this->assertStringContainsString('`Acme\\Domains\\Core`', $text);
        $this->assertStringContainsString('Acme\\Domains\\Core\\BaseService', $text);
        $this->assertStringContainsString('custom/Domains/Core/{BaseModel,BaseApiController,BaseService}.php', $text);
    }

    public function test_ai_context_default_core_namespace_unchanged()
    {
        $text = $this->coreMentions();

        $this->assertStringContainsString('`App\\Modules\\Core`', $text);
        $this->assertStringContainsString('App\\Modules\\Core\\BaseService    ->  PersonService', $text);
        $this->assertStringContainsString('-> `App\\Modules\\Core\\*` bridge', $text);
        $this->assertStringContainsString('the App\\Modules\\Core bridge classes', $text);
        $this->assertStringContainsString('app/Modules/Core/{BaseModel,BaseApiController,BaseService}.php', $text);
    }
}

<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\Tests\TestCase;

class AiInitTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('.ai'));
        File::deleteDirectory(base_path('.cursor'));
        File::deleteDirectory(app_path('Modules'));
        File::delete(base_path('CLAUDE.md'));
        File::delete(base_path('.mcp.json'));
        File::delete(base_path('AGENTS.md'));
        File::delete(config_path('module-ai.php'));

        parent::tearDown();
    }

    public function test_generates_canonical_context_and_platform_files()
    {
        Artisan::call('jw:ai:init', ['--platforms' => 'claude,cursor']);

        // Canonical .ai/ context
        $this->assertTrue(File::exists(base_path('.ai/summary.md')));
        $this->assertTrue(File::exists(base_path('.ai/architecture.md')));
        $this->assertTrue(File::exists(base_path('.ai/generators.md')));
        $this->assertTrue(File::exists(base_path('.ai/ai-rules.md')));

        // Generator catalog is reflected from live commands
        $generators = File::get(base_path('.ai/generators.md'));
        $this->assertStringContainsString('jw:make-model', $generators);
        $this->assertStringContainsString('--module=', $generators);

        // Claude entry: managed block + MCP registration
        $claudeMd = File::get(base_path('CLAUDE.md'));
        $this->assertStringContainsString('<!-- module-maker:ai:begin -->', $claudeMd);
        $this->assertStringContainsString('<!-- module-maker:ai:end -->', $claudeMd);

        $mcp = json_decode(File::get(base_path('.mcp.json')), true);
        $this->assertSame('php', $mcp['mcpServers']['module-maker']['command']);
        $this->assertSame(['artisan', 'jw:mcp'], $mcp['mcpServers']['module-maker']['args']);

        // Cursor entry
        $this->assertTrue(File::exists(base_path('.cursor/rules/module-maker.mdc')));
        $this->assertTrue(File::exists(base_path('.cursor/mcp.json')));

        // Localized config persisted
        $this->assertTrue(File::exists(config_path('module-ai.php')));
        $this->assertStringContainsString("'claude', 'cursor'", File::get(config_path('module-ai.php')));

        // No unreplaced stub placeholders leaked into output
        foreach (File::files(base_path('.ai')) as $file) {
            $this->assertStringNotContainsString('{{ ', File::get($file->getPathname()), "Unreplaced placeholder in {$file->getFilename()}");
        }
    }

    public function test_managed_block_preserves_user_content_and_is_idempotent()
    {
        File::put(base_path('CLAUDE.md'), "# My project notes\n\nKeep me.\n");

        Artisan::call('jw:ai:init', ['--platforms' => 'claude']);
        $first = File::get(base_path('CLAUDE.md'));

        $this->assertStringContainsString('Keep me.', $first);
        $this->assertStringContainsString('module-maker:ai:begin', $first);

        Artisan::call('jw:ai:init', ['--platforms' => 'claude']);
        $second = File::get(base_path('CLAUDE.md'));

        $this->assertSame($first, $second);
        $this->assertSame(1, substr_count($second, 'module-maker:ai:begin'));
    }

    public function test_summary_depth_writes_only_summary()
    {
        Artisan::call('jw:ai:init', ['--platforms' => 'claude', '--depth' => 'summary']);

        $this->assertTrue(File::exists(base_path('.ai/summary.md')));
        $this->assertFalse(File::exists(base_path('.ai/architecture.md')));
    }

    public function test_no_mcp_flag_skips_registration()
    {
        Artisan::call('jw:ai:init', ['--platforms' => 'claude', '--no-mcp' => true]);

        $this->assertFalse(File::exists(base_path('.mcp.json')));
        $this->assertTrue(File::exists(base_path('CLAUDE.md')));
    }

    public function test_dry_run_writes_nothing()
    {
        Artisan::call('jw:ai:init', ['--platforms' => 'claude', '--dry-run' => true]);

        $this->assertFalse(File::exists(base_path('.ai/summary.md')));
        $this->assertFalse(File::exists(base_path('CLAUDE.md')));
    }

    public function test_summary_reflects_existing_modules()
    {
        Artisan::call('jw:make-module', ['name' => 'Billing']);
        Artisan::call('jw:ai:init', ['--platforms' => 'claude']);

        $this->assertStringContainsString('Billing', File::get(base_path('.ai/summary.md')));
    }

    public function test_unknown_platform_fails()
    {
        $exit = Artisan::call('jw:ai:init', ['--platforms' => 'nonexistent']);

        $this->assertSame(1, $exit);
        $this->assertFalse(File::exists(base_path('.ai/summary.md')));
    }
}

<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Mcp\Server;
use Jackwander\ModuleMaker\Commands\McpServe;
use Jackwander\ModuleMaker\Tests\TestCase;

class McpServerTest extends TestCase
{
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = new Server(McpServe::buildToolRegistry());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Modules'));
        File::deleteDirectory(base_path('.ai'));

        parent::tearDown();
    }

    protected function request(string $method, array $params = [], $id = 1): ?array
    {
        return $this->server->handle([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ]);
    }

    protected function callTool(string $name, array $arguments = []): array
    {
        return $this->request('tools/call', ['name' => $name, 'arguments' => $arguments])['result'];
    }

    public function test_initialize_handshake()
    {
        $response = $this->request('initialize', ['protocolVersion' => '2024-11-05']);

        $this->assertSame('module-maker', $response['result']['serverInfo']['name']);
        $this->assertSame('2024-11-05', $response['result']['protocolVersion']);
    }

    public function test_notifications_get_no_response()
    {
        $this->assertNull($this->server->handle(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']));
    }

    public function test_unknown_method_returns_jsonrpc_error()
    {
        $response = $this->request('bogus/method');

        $this->assertSame(-32601, $response['error']['code']);
    }

    public function test_tools_list_exposes_expected_tools()
    {
        $tools = collect($this->request('tools/list')['result']['tools'])->pluck('name');

        foreach (['application_info', 'list_modules', 'module_structure', 'list_generators', 'generator_info', 'get_guidelines', 'run_generator'] as $tool) {
            $this->assertContains($tool, $tools);
        }
    }

    public function test_list_generators_returns_catalog()
    {
        $result = $this->callTool('list_generators');

        $this->assertStringContainsString('jw:make-model', $result['content'][0]['text']);
        $this->assertStringContainsString('jw:make-module', $result['content'][0]['text']);
    }

    public function test_generator_info_details_a_command()
    {
        $result = $this->callTool('generator_info', ['command' => 'jw:make-model']);
        $data = json_decode($result['content'][0]['text'], true);

        $this->assertSame('jw:make-model', $data['name']);
        $this->assertContains('module', array_column($data['options'], 'name'));
    }

    public function test_get_guidelines_renders_live_sections()
    {
        $result = $this->callTool('get_guidelines', ['topic' => 'conventions', 'depth' => 'compressed']);

        $this->assertStringContainsString('plural snake_case', $result['content'][0]['text']);

        $summary = $this->callTool('get_guidelines', ['topic' => 'summary']);
        $this->assertStringContainsString('jw:make-module', $summary['content'][0]['text']);
    }

    public function test_get_guidelines_rejects_unknown_topic()
    {
        $result = $this->callTool('get_guidelines', ['topic' => 'nope']);

        $this->assertTrue($result['isError']);
    }

    public function test_module_structure_lists_module_files()
    {
        Artisan::call('jw:make-module', ['name' => 'Demo']);

        $result = $this->callTool('module_structure', ['module' => 'Demo']);

        $this->assertStringContainsString('Models/Demo.php', $result['content'][0]['text']);
        $this->assertStringContainsString('Services/DemoService.php', $result['content'][0]['text']);
    }

    public function test_run_generator_dry_run_previews_without_executing()
    {
        $result = $this->callTool('run_generator', [
            'command' => 'jw:make-module',
            'arguments' => ['name' => 'Ghost'],
            'dry_run' => true,
        ]);

        $this->assertStringContainsString('DRY RUN', $result['content'][0]['text']);
        $this->assertFalse(File::exists(app_path('Modules/Ghost')));
    }

    public function test_run_generator_executes_real_generation()
    {
        $result = $this->callTool('run_generator', [
            'command' => 'jw:make-module',
            'arguments' => ['name' => 'Billing'],
            'dry_run' => false,
        ]);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertTrue(File::exists(app_path('Modules/Billing/Services/BillingService.php')));
    }

    public function test_run_generator_blocks_non_generator_commands()
    {
        foreach (['jw:mcp', 'jw:ai:init', 'migrate:fresh', 'db:wipe'] as $blocked) {
            $result = $this->callTool('run_generator', ['command' => $blocked, 'dry_run' => false]);

            $this->assertTrue($result['isError'] ?? false, "{$blocked} should be blocked");
        }
    }

    public function test_run_generator_respects_config_kill_switch()
    {
        config()->set('module-ai.mcp.allow_run_generator', false);

        $server = new Server(McpServe::buildToolRegistry());
        $tools = collect($server->handle([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => [],
        ])['result']['tools'])->pluck('name');

        $this->assertNotContains('run_generator', $tools);
    }
}

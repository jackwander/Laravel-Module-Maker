<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Support\JsonMerger;
use Jackwander\ModuleMaker\AI\Support\ManagedBlock;
use Jackwander\ModuleMaker\Tests\TestCase;

class ManagedBlockTest extends TestCase
{
    protected string $path;
    protected string $jsonPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = base_path('managed-block-test.md');
        $this->jsonPath = base_path('json-merge-test.json');
    }

    protected function tearDown(): void
    {
        File::delete($this->path);
        File::delete($this->jsonPath);

        parent::tearDown();
    }

    public function test_creates_file_when_missing()
    {
        $status = (new ManagedBlock())->apply($this->path, 'test:block', 'Hello');

        $this->assertSame('created', $status);
        $this->assertStringContainsString("<!-- test:block:begin -->\nHello\n<!-- test:block:end -->", File::get($this->path));
    }

    public function test_appends_block_and_preserves_existing_content()
    {
        File::put($this->path, "# User content\n");

        $status = (new ManagedBlock())->apply($this->path, 'test:block', 'Generated');
        $content = File::get($this->path);

        $this->assertSame('updated', $status);
        $this->assertStringContainsString('# User content', $content);
        $this->assertStringContainsString('Generated', $content);
    }

    public function test_replaces_only_the_managed_region()
    {
        $block = new ManagedBlock();
        File::put($this->path, "before\n");
        $block->apply($this->path, 'test:block', 'v1');
        File::append($this->path, "\nafter\n");

        $block->apply($this->path, 'test:block', 'v2');
        $content = File::get($this->path);

        $this->assertStringContainsString('before', $content);
        $this->assertStringContainsString('after', $content);
        $this->assertStringContainsString('v2', $content);
        $this->assertStringNotContainsString('v1', $content);
        $this->assertSame(1, substr_count($content, 'test:block:begin'));
    }

    public function test_unchanged_when_content_identical()
    {
        $block = new ManagedBlock();
        $block->apply($this->path, 'test:block', 'same');

        $this->assertSame('unchanged', $block->apply($this->path, 'test:block', 'same'));
    }

    public function test_json_merger_preserves_foreign_keys()
    {
        File::put($this->jsonPath, json_encode(['mcpServers' => ['other' => ['command' => 'node']]]));

        $merger = new JsonMerger();
        $status = $merger->merge($this->jsonPath, ['mcpServers' => ['module-maker' => ['command' => 'php']]]);

        $data = json_decode(File::get($this->jsonPath), true);

        $this->assertSame('updated', $status);
        $this->assertSame('node', $data['mcpServers']['other']['command']);
        $this->assertSame('php', $data['mcpServers']['module-maker']['command']);
        $this->assertSame('unchanged', $merger->merge($this->jsonPath, ['mcpServers' => ['module-maker' => ['command' => 'php']]]));
    }
}

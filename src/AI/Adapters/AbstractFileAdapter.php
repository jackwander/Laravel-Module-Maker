<?php

namespace Jackwander\ModuleMaker\AI\Adapters;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Contracts\PlatformAdapter;
use Jackwander\ModuleMaker\AI\Support\JsonMerger;
use Jackwander\ModuleMaker\AI\Support\ManagedBlock;
use Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

abstract class AbstractFileAdapter implements PlatformAdapter
{
    use InteractsWithStubs;

    public function __construct(
        protected ManagedBlock $managedBlock,
        protected JsonMerger $jsonMerger,
    ) {
    }

    public function detected(): bool
    {
        return false;
    }

    /** Full-overwrite write for wholly-owned entry files. */
    protected function putOwned(string $path, string $content): string
    {
        $status = File::exists($path)
            ? (File::get($path) === $content ? 'unchanged' : 'updated')
            : 'created';

        if ($status !== 'unchanged') {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $content);
        }

        return $status;
    }

    /** Standard MCP server registration payload. */
    protected function mcpServerEntry(): array
    {
        return [
            'mcpServers' => [
                'module-maker' => [
                    'command' => 'php',
                    'args' => ['artisan', 'jw:mcp'],
                ],
            ],
        ];
    }
}

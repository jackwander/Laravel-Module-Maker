<?php

namespace Jackwander\ModuleMaker\AI\Adapters;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class CodexAdapter extends AbstractFileAdapter
{
    public function name(): string
    {
        return 'codex';
    }

    public function label(): string
    {
        return 'OpenAI Codex / AGENTS.md standard';
    }

    public function detected(): bool
    {
        return File::exists(base_path('AGENTS.md'));
    }

    public function plannedFiles(ProjectSnapshot $snapshot): array
    {
        return ['AGENTS.md (managed block)'];
    }

    public function write(ProjectSnapshot $snapshot, Depth $depth, bool $registerMcp): array
    {
        $block = $this->getStubContent('ai/generic-entry', [
            'packageVersion' => $snapshot->packageVersion,
            'mcpNote' => 'MCP: register `php artisan jw:mcp` (stdio) in your client config; the project `.mcp.json` already carries the entry.',
        ]);

        return [
            'AGENTS.md' => $this->managedBlock->apply(base_path('AGENTS.md'), 'module-maker:ai', $block),
        ];
    }
}

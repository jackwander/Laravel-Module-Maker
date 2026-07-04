<?php

namespace Jackwander\ModuleMaker\AI\Adapters;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class ClaudeAdapter extends AbstractFileAdapter
{
    public function name(): string
    {
        return 'claude';
    }

    public function label(): string
    {
        return 'Claude Code (CLAUDE.md + .mcp.json)';
    }

    public function detected(): bool
    {
        return File::exists(base_path('CLAUDE.md')) || File::isDirectory(base_path('.claude'));
    }

    public function plannedFiles(ProjectSnapshot $snapshot): array
    {
        return ['CLAUDE.md (managed block)', '.mcp.json (merged)'];
    }

    public function write(ProjectSnapshot $snapshot, Depth $depth, bool $registerMcp): array
    {
        $results = [];

        $block = $this->getStubContent('ai/claude-entry', [
            'packageVersion' => $snapshot->packageVersion,
        ]);

        $results['CLAUDE.md'] = $this->managedBlock->apply(base_path('CLAUDE.md'), 'module-maker:ai', $block);

        if ($registerMcp) {
            $results['.mcp.json'] = $this->jsonMerger->merge(base_path('.mcp.json'), $this->mcpServerEntry());
        }

        return $results;
    }
}

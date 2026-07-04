<?php

namespace Jackwander\ModuleMaker\AI\Adapters;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class CursorAdapter extends AbstractFileAdapter
{
    public function name(): string
    {
        return 'cursor';
    }

    public function label(): string
    {
        return 'Cursor (.cursor/rules + .cursor/mcp.json)';
    }

    public function detected(): bool
    {
        return File::isDirectory(base_path('.cursor'));
    }

    public function plannedFiles(ProjectSnapshot $snapshot): array
    {
        return ['.cursor/rules/module-maker.mdc', '.cursor/mcp.json (merged)'];
    }

    public function write(ProjectSnapshot $snapshot, Depth $depth, bool $registerMcp): array
    {
        $results = [];

        $content = $this->getStubContent('ai/cursor-entry', [
            'packageVersion' => $snapshot->packageVersion,
        ]);

        $results['.cursor/rules/module-maker.mdc'] = $this->putOwned(
            base_path('.cursor/rules/module-maker.mdc'),
            $content
        );

        if ($registerMcp) {
            $results['.cursor/mcp.json'] = $this->jsonMerger->merge(base_path('.cursor/mcp.json'), $this->mcpServerEntry());
        }

        return $results;
    }
}

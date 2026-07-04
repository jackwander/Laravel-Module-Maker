<?php

namespace Jackwander\ModuleMaker\AI\Adapters;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class CopilotAdapter extends AbstractFileAdapter
{
    public function name(): string
    {
        return 'copilot';
    }

    public function label(): string
    {
        return 'GitHub Copilot (.github/copilot-instructions.md)';
    }

    public function detected(): bool
    {
        return File::exists(base_path('.github/copilot-instructions.md'));
    }

    public function plannedFiles(ProjectSnapshot $snapshot): array
    {
        return ['.github/copilot-instructions.md (managed block)'];
    }

    public function write(ProjectSnapshot $snapshot, Depth $depth, bool $registerMcp): array
    {
        $block = $this->getStubContent('ai/generic-entry', [
            'packageVersion' => $snapshot->packageVersion,
            'mcpNote' => 'This platform does not consume project-level MCP config; rely on the static `.ai/` context.',
        ]);

        return [
            '.github/copilot-instructions.md' => $this->managedBlock->apply(
                base_path('.github/copilot-instructions.md'),
                'module-maker:ai',
                $block
            ),
        ];
    }
}

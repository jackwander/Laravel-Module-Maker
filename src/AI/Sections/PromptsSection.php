<?php

namespace Jackwander\ModuleMaker\AI\Sections;

use Jackwander\ModuleMaker\AI\Contracts\Section;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;
use Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

class PromptsSection implements Section
{
    use InteractsWithStubs;

    public function key(): string
    {
        return 'prompts';
    }

    public function title(): string
    {
        return 'Reusable Prompt Templates';
    }

    public function render(ProjectSnapshot $snapshot, Depth $depth): string
    {
        return $this->getStubContent('ai/prompts', [
            'testCommand' => $snapshot->testCommand(),
            'modulesPath' => $snapshot->modulesPath,
        ]);
    }
}

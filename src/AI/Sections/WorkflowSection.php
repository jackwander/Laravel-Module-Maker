<?php

namespace Jackwander\ModuleMaker\AI\Sections;

use Jackwander\ModuleMaker\AI\Contracts\Section;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;
use Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

class WorkflowSection implements Section
{
    use InteractsWithStubs;

    public function key(): string
    {
        return 'workflow';
    }

    public function title(): string
    {
        return 'Feature Development Workflow';
    }

    public function render(ProjectSnapshot $snapshot, Depth $depth): string
    {
        return $this->getStubContent('ai/workflow', [
            'apiPrefix' => $snapshot->apiPrefix,
            'modulesPath' => $snapshot->modulesPath,
            'testCommand' => $snapshot->testCommand(),
        ]);
    }
}

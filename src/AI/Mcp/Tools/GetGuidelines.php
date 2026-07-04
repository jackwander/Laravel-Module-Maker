<?php

namespace Jackwander\ModuleMaker\AI\Mcp\Tools;

use Jackwander\ModuleMaker\AI\ContextBuilder;
use Jackwander\ModuleMaker\AI\Inspector;
use Jackwander\ModuleMaker\AI\Mcp\Contracts\Tool;
use Jackwander\ModuleMaker\AI\Support\Depth;

class GetGuidelines implements Tool
{
    public function __construct(
        protected ContextBuilder $builder,
        protected Inspector $inspector,
    ) {
    }

    public function name(): string
    {
        return 'get_guidelines';
    }

    public function description(): string
    {
        return 'Render one guideline topic live at the requested depth. Topics: summary, architecture, conventions, generators, ai-rules, modules, workflow, tooling, prompts.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'topic' => ['type' => 'string', 'description' => 'Guideline topic key'],
                'depth' => ['type' => 'string', 'enum' => ['full', 'compressed'], 'description' => 'Verbosity (default: full)'],
            ],
            'required' => ['topic'],
        ];
    }

    public function handle(array $arguments): string
    {
        $topic = (string) ($arguments['topic'] ?? '');
        $depth = Depth::fromString($arguments['depth'] ?? 'full');
        $snapshot = $this->inspector->inspect();

        if ($topic === 'summary') {
            return $this->builder->buildSummary($snapshot);
        }

        $sections = $this->builder->sections();

        if (! isset($sections[$topic])) {
            throw new \InvalidArgumentException(
                "Unknown topic [{$topic}]. Available: summary, " . implode(', ', array_keys($sections))
            );
        }

        return $sections[$topic]->render($snapshot, $depth);
    }
}

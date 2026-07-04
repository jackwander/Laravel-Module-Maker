<?php

namespace Jackwander\ModuleMaker\AI\Mcp\Tools;

use Jackwander\ModuleMaker\AI\Inspector;
use Jackwander\ModuleMaker\AI\Mcp\Contracts\Tool;

class ListModules implements Tool
{
    public function __construct(protected Inspector $inspector)
    {
    }

    public function name(): string
    {
        return 'list_modules';
    }

    public function description(): string
    {
        return 'List all modules in the project with the components each one contains. Check this before creating anything to avoid duplicating a domain.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => (object) [], 'required' => []];
    }

    public function handle(array $arguments): string
    {
        $modules = $this->inspector->inspect()->modules;

        if (! $modules) {
            return 'No modules exist yet. Create one with: php artisan jw:make-module {Name}';
        }

        return json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

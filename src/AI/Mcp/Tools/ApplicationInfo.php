<?php

namespace Jackwander\ModuleMaker\AI\Mcp\Tools;

use Jackwander\ModuleMaker\AI\Inspector;
use Jackwander\ModuleMaker\AI\Mcp\Contracts\Tool;

class ApplicationInfo implements Tool
{
    public function __construct(protected Inspector $inspector)
    {
    }

    public function name(): string
    {
        return 'application_info';
    }

    public function description(): string
    {
        return 'PHP/Laravel/package versions, module-maker configuration, detected tooling (pint, phpstan, pest, ...), and bridge status.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => (object) [], 'required' => []];
    }

    public function handle(array $arguments): string
    {
        $snapshot = $this->inspector->inspect();

        return json_encode([
            'php' => $snapshot->phpVersion,
            'laravel' => $snapshot->laravelVersion,
            'module_maker' => $snapshot->packageVersion,
            'modules_path' => $snapshot->modulesPath,
            'root_namespace' => $snapshot->rootNamespace,
            'api_prefix' => $snapshot->apiPrefix,
            'base_classes' => $snapshot->baseClasses,
            'core_bridge_active' => $snapshot->bridgeActive,
            'tooling' => $snapshot->tooling,
            'published_stubs' => $snapshot->publishedStubs,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

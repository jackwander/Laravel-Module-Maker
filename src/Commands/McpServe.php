<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Jackwander\ModuleMaker\AI\Mcp\Server;
use Jackwander\ModuleMaker\AI\Mcp\ToolRegistry;
use Jackwander\ModuleMaker\AI\Mcp\Tools\ApplicationInfo;
use Jackwander\ModuleMaker\AI\Mcp\Tools\GeneratorInfo;
use Jackwander\ModuleMaker\AI\Mcp\Tools\GetGuidelines;
use Jackwander\ModuleMaker\AI\Mcp\Tools\ListGenerators;
use Jackwander\ModuleMaker\AI\Mcp\Tools\ListModules;
use Jackwander\ModuleMaker\AI\Mcp\Tools\ModuleStructure;
use Jackwander\ModuleMaker\AI\Mcp\Tools\RunGenerator;

class McpServe extends Command
{
    protected $signature = 'jw:mcp';

    protected $description = 'Run the Module Maker MCP server (stdio JSON-RPC) for AI coding assistants';

    public function handle()
    {
        $server = new Server(static::buildToolRegistry());

        $server->run();

        return 0;
    }

    public static function buildToolRegistry(): ToolRegistry
    {
        $registry = new ToolRegistry();

        $registry->register(app(ApplicationInfo::class));
        $registry->register(app(ListModules::class));
        $registry->register(app(ModuleStructure::class));
        $registry->register(app(ListGenerators::class));
        $registry->register(app(GeneratorInfo::class));
        $registry->register(app(GetGuidelines::class));

        if (config('module-ai.mcp.allow_run_generator', true)) {
            $registry->register(app(RunGenerator::class));
        }

        return $registry;
    }
}

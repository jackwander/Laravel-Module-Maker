<?php

namespace Jackwander\ModuleMaker\AI\Mcp\Tools;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Mcp\Contracts\Tool;

class ModuleStructure implements Tool
{
    public function name(): string
    {
        return 'module_structure';
    }

    public function description(): string
    {
        return 'Full file tree of one module (paths relative to the module root).';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'module' => ['type' => 'string', 'description' => 'Module directory name, e.g. "Person"'],
            ],
            'required' => ['module'],
        ];
    }

    public function handle(array $arguments): string
    {
        $module = basename((string) ($arguments['module'] ?? ''));
        $modulesPath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$modulesPath}/{$module}";

        if ($module === '' || ! File::isDirectory($modulePath)) {
            throw new \InvalidArgumentException("Module [{$module}] not found in {$modulesPath}.");
        }

        $files = collect(File::allFiles($modulePath))
            ->map(fn ($file) => $file->getRelativePathname())
            ->sort()
            ->values()
            ->all();

        return "Module: {$module}\nPath: {$modulePath}\n\n" . implode("\n", $files);
    }
}

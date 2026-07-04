<?php

namespace Jackwander\ModuleMaker\AI\Mcp\Tools;

use Jackwander\ModuleMaker\AI\CommandCatalog;
use Jackwander\ModuleMaker\AI\Mcp\Contracts\Tool;

class GeneratorInfo implements Tool
{
    public function __construct(protected CommandCatalog $catalog)
    {
    }

    public function name(): string
    {
        return 'generator_info';
    }

    public function description(): string
    {
        return 'Full detail for one jw:* command: arguments, options, and the files it creates.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => ['type' => 'string', 'description' => 'Command name, e.g. "jw:make-model"'],
            ],
            'required' => ['command'],
        ];
    }

    public function handle(array $arguments): string
    {
        $name = (string) ($arguments['command'] ?? '');
        $entry = $this->catalog->find($name);

        if (! $entry) {
            throw new \InvalidArgumentException("Unknown command [{$name}]. Use list_generators for the catalog.");
        }

        return json_encode($entry + ['usage' => 'php artisan ' . $this->catalog->usage($entry)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

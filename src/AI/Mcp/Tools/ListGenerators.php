<?php

namespace Jackwander\ModuleMaker\AI\Mcp\Tools;

use Jackwander\ModuleMaker\AI\CommandCatalog;
use Jackwander\ModuleMaker\AI\Mcp\Contracts\Tool;

class ListGenerators implements Tool
{
    public function __construct(protected CommandCatalog $catalog)
    {
    }

    public function name(): string
    {
        return 'list_generators';
    }

    public function description(): string
    {
        return 'List every jw:* artisan generator with usage and description. These are the only sanctioned way to create module files.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => (object) [], 'required' => []];
    }

    public function handle(array $arguments): string
    {
        $lines = [];

        foreach ($this->catalog->all() as $entry) {
            $lines[] = 'php artisan ' . $this->catalog->usage($entry) . "\n    " . $entry['description'];
        }

        return implode("\n\n", $lines);
    }
}

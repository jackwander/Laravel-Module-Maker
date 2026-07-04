<?php

namespace Jackwander\ModuleMaker\AI\Mcp;

use Jackwander\ModuleMaker\AI\Mcp\Contracts\Tool;

class ToolRegistry
{
    /** @var array<string, Tool> */
    protected array $tools = [];

    public function register(Tool $tool): static
    {
        $this->tools[$tool->name()] = $tool;

        return $this;
    }

    public function extend(Tool $tool): static
    {
        return $this->register($tool);
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function schemas(): array
    {
        return array_values(array_map(fn (Tool $tool) => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'inputSchema' => $tool->inputSchema(),
        ], $this->tools));
    }

    /** @return array MCP tools/call result payload */
    public function call(string $name, array $arguments): array
    {
        if (! $this->has($name)) {
            return [
                'content' => [['type' => 'text', 'text' => "Unknown tool [{$name}]."]],
                'isError' => true,
            ];
        }

        try {
            $text = $this->tools[$name]->handle($arguments);

            return ['content' => [['type' => 'text', 'text' => $text]]];
        } catch (\Throwable $e) {
            return [
                'content' => [['type' => 'text', 'text' => 'Tool error: ' . $e->getMessage()]],
                'isError' => true,
            ];
        }
    }
}

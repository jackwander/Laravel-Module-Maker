<?php

namespace Jackwander\ModuleMaker\AI\Mcp\Contracts;

interface Tool
{
    public function name(): string;

    public function description(): string;

    /** JSON Schema for the tool's arguments. */
    public function inputSchema(): array;

    /** Execute and return plain text for the MCP text content block. */
    public function handle(array $arguments): string;
}

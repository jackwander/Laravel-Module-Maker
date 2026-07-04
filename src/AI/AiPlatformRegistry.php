<?php

namespace Jackwander\ModuleMaker\AI;

use Jackwander\ModuleMaker\AI\Adapters\ClaudeAdapter;
use Jackwander\ModuleMaker\AI\Adapters\CodexAdapter;
use Jackwander\ModuleMaker\AI\Adapters\CopilotAdapter;
use Jackwander\ModuleMaker\AI\Adapters\CursorAdapter;
use Jackwander\ModuleMaker\AI\Contracts\PlatformAdapter;

class AiPlatformRegistry
{
    /** @var array<string, callable> */
    protected array $factories = [];

    public function __construct()
    {
        $this->extend('claude', fn () => app(ClaudeAdapter::class));
        $this->extend('cursor', fn () => app(CursorAdapter::class));
        $this->extend('copilot', fn () => app(CopilotAdapter::class));
        $this->extend('codex', fn () => app(CodexAdapter::class));

        foreach (config('module-ai.adapters', []) as $name => $class) {
            $this->extend($name, fn () => app($class));
        }
    }

    public function extend(string $name, callable $factory): static
    {
        $this->factories[$name] = $factory;

        return $this;
    }

    public function names(): array
    {
        return array_keys($this->factories);
    }

    public function has(string $name): bool
    {
        return isset($this->factories[$name]);
    }

    public function get(string $name): PlatformAdapter
    {
        if (! $this->has($name)) {
            throw new \InvalidArgumentException("Unknown AI platform [{$name}]. Available: " . implode(', ', $this->names()));
        }

        return ($this->factories[$name])();
    }

    /** @return PlatformAdapter[] keyed by name */
    public function resolve(array $names): array
    {
        $adapters = [];

        foreach ($names as $name) {
            $adapters[$name] = $this->get($name);
        }

        return $adapters;
    }
}

<?php

namespace Jackwander\ModuleMaker\AI\Mcp\Tools;

use Illuminate\Support\Facades\Artisan;
use Jackwander\ModuleMaker\AI\CommandCatalog;
use Jackwander\ModuleMaker\AI\Mcp\Contracts\Tool;
use Symfony\Component\Console\Output\BufferedOutput;

class RunGenerator implements Tool
{
    /** Commands that are never callable through MCP. */
    protected const BLOCKED = ['jw:mcp', 'jw:ai:init'];

    public function __construct(protected CommandCatalog $catalog)
    {
    }

    public function name(): string
    {
        return 'run_generator';
    }

    public function description(): string
    {
        return 'Execute a jw:* generator so scaffolding follows package conventions by construction. Always call with dry_run=true first to review, then re-run with dry_run=false.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => ['type' => 'string', 'description' => 'Generator name, e.g. "jw:make-model"'],
                'arguments' => [
                    'type' => 'object',
                    'description' => 'Artisan parameters, e.g. {"name": "CivilStatus", "--module": "Person", "--all": true}',
                ],
                'dry_run' => ['type' => 'boolean', 'description' => 'Preview without executing (default: true)'],
            ],
            'required' => ['command'],
        ];
    }

    public function handle(array $arguments): string
    {
        if (! config('module-ai.mcp.allow_run_generator', true)) {
            throw new \InvalidArgumentException('run_generator is disabled by config module-ai.mcp.allow_run_generator.');
        }

        $command = (string) ($arguments['command'] ?? '');
        $parameters = (array) ($arguments['arguments'] ?? []);
        $dryRun = (bool) ($arguments['dry_run'] ?? true);

        $entry = $this->catalog->find($command);

        if (! $entry || in_array($command, self::BLOCKED)) {
            throw new \InvalidArgumentException("[{$command}] is not an allowed generator. Use list_generators for the catalog.");
        }

        $rendered = collect($parameters)
            ->map(fn ($value, $key) => $value === true ? $key : "{$key}={$value}")
            ->implode(' ');

        if ($dryRun) {
            return "DRY RUN — would execute:\n\nphp artisan {$command} {$rendered}\n\n"
                . 'Creates: ' . (implode(', ', $entry['creates']) ?: '(nothing)') . "\n\n"
                . 'Re-run with dry_run=false to execute.';
        }

        $output = new BufferedOutput();
        $exitCode = Artisan::call($command, $parameters, $output);

        return "Executed: php artisan {$command} {$rendered}\nExit code: {$exitCode}\n\n" . trim($output->fetch());
    }
}

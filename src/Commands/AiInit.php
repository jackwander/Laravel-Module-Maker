<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Jackwander\ModuleMaker\AI\AiPlatformRegistry;
use Jackwander\ModuleMaker\AI\ContextBuilder;
use Jackwander\ModuleMaker\AI\Inspector;
use Jackwander\ModuleMaker\AI\Support\Depth;

class AiInit extends Command
{
    protected $signature = 'jw:ai:init
                    {--platforms= : Comma-separated platform keys (claude,cursor,copilot,codex). Omit for interactive selection}
                    {--depth= : Context depth: full, compressed, or summary}
                    {--no-mcp : Skip MCP server registration in platform configs}
                    {--dry-run : Show the file plan without writing anything}
                    {--refresh : Non-interactive; regenerate using the saved module-ai config}';

    protected $description = 'Generate AI assistant context files (.ai/) and platform integrations (CLAUDE.md, .cursor, MCP, ...)';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(Inspector $inspector, ContextBuilder $builder, AiPlatformRegistry $registry)
    {
        $this->info('🤖 Module Maker — AI Context Generator');

        try {
            $platforms = $this->resolvePlatforms($registry);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $depth = Depth::fromString($this->option('depth') ?: config('module-ai.depth', 'full'));
        $registerMcp = ! $this->option('no-mcp') && config('module-ai.mcp.enabled', true);

        $snapshot = $inspector->inspect();
        $adapters = $registry->resolve($platforms);

        $this->newLine();
        $this->line("  PHP {$snapshot->phpVersion} · Laravel {$snapshot->laravelVersion} · module-maker {$snapshot->packageVersion}");
        $this->line('  Platforms: ' . implode(', ', $platforms) . " · Depth: {$depth->value}" . ($registerMcp ? ' · MCP: on' : ' · MCP: off'));
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->comment('Dry run — no files written. Plan:');

            foreach ($builder->plannedFiles($depth) as $file) {
                $this->components->twoColumnDetail($file, '<fg=cyan>planned</>');
            }

            foreach ($adapters as $adapter) {
                foreach ($adapter->plannedFiles($snapshot) as $file) {
                    $this->components->twoColumnDetail($file, '<fg=cyan>planned</>');
                }
            }

            return 0;
        }

        foreach ($builder->build($snapshot, $depth) as $file => $status) {
            $this->components->twoColumnDetail(".ai/{$file}", $this->badge($status));
        }

        foreach ($adapters as $adapter) {
            foreach ($adapter->write($snapshot, $depth, $registerMcp) as $file => $status) {
                $this->components->twoColumnDetail($file, $this->badge($status));
            }
        }

        $this->persistConfig($platforms, $depth);

        $this->newLine();
        $this->info('✅ AI context generated.');
        $this->line('Refresh anytime with <fg=cyan>php artisan jw:ai:init --refresh</> (e.g. after adding modules or tooling).');

        return 0;
    }

    protected function resolvePlatforms(AiPlatformRegistry $registry): array
    {
        if ($option = $this->option('platforms')) {
            $platforms = array_values(array_filter(array_map('trim', explode(',', $option))));
        } elseif ($this->option('refresh')) {
            $platforms = config('module-ai.platforms', ['claude']);
        } else {
            $platforms = $this->askPlatforms($registry);
        }

        foreach ($platforms as $platform) {
            $registry->get($platform); // throws on unknown
        }

        return $platforms;
    }

    protected function askPlatforms(AiPlatformRegistry $registry): array
    {
        // Associative choices (name => label): Symfony renders the label but
        // returns the platform key, and its multiselect default is resolved by
        // key — an indexed list here would trip writePrompt's $choices[$key]
        // lookup and warn "Undefined array key".
        $choices = [];
        $detected = [];

        foreach ($registry->names() as $name) {
            $adapter = $registry->get($name);
            $choices[$name] = $adapter->label();

            if ($adapter->detected()) {
                $detected[] = $name;
            }
        }

        $chosen = $this->choice(
            'Which AI platforms should be configured?',
            $choices,
            $detected ? implode(',', $detected) : array_key_first($choices),
            null,
            true
        );

        return array_values((array) $chosen);
    }

    protected function persistConfig(array $platforms, Depth $depth): void
    {
        $configPath = config_path('module-ai.php');

        if ($this->files->exists($configPath)) {
            return; // never clobber a customized host config
        }

        $platformsExport = "'" . implode("', '", $platforms) . "'";

        $content = str_replace(
            ['{{ platforms }}', '{{ depth }}'],
            [$platformsExport, $depth->value],
            $this->files->get(dirname(__DIR__, 2) . '/stubs/ai/module-ai-config.stub')
        );

        $this->files->put($configPath, $content);
        $this->components->twoColumnDetail('config/module-ai.php', '<fg=green>Localized</>');
    }

    protected function badge(string $status): string
    {
        return match ($status) {
            'created' => '<fg=green>Created</>',
            'updated' => '<fg=yellow>Updated</>',
            'unchanged' => '<fg=gray>Unchanged</>',
            default => $status,
        };
    }
}

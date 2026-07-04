<?php

namespace Jackwander\ModuleMaker\AI\Sections;

use Jackwander\ModuleMaker\AI\Contracts\Section;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class ToolingSection implements Section
{
    protected const COMMANDS = [
        'pint' => 'vendor/bin/pint',
        'phpstan' => 'vendor/bin/phpstan analyse',
        'psalm' => 'vendor/bin/psalm',
        'rector' => 'vendor/bin/rector process --dry-run',
        'pest' => 'vendor/bin/pest',
        'phpunit' => 'vendor/bin/phpunit',
        'sail' => './vendor/bin/sail up -d',
    ];

    public function key(): string
    {
        return 'tooling';
    }

    public function title(): string
    {
        return 'Detected Tooling';
    }

    public function render(ProjectSnapshot $snapshot, Depth $depth): string
    {
        $out = "# {$this->title()}\n\n";
        $out .= "Environment: PHP {$snapshot->phpVersion} · Laravel {$snapshot->laravelVersion} · module-maker {$snapshot->packageVersion}\n\n";

        $detected = [];
        foreach (self::COMMANDS as $key => $command) {
            if ($snapshot->toolingEnabled($key)) {
                $detected[] = "- **{$key}**: `{$command}`";
            }
        }

        if ($snapshot->toolingEnabled('docker')) {
            $detected[] = '- **docker**: `docker compose up -d`';
        }
        if ($snapshot->toolingEnabled('ci')) {
            $detected[] = '- **CI**: GitHub Actions workflows in `.github/workflows`';
        }

        $out .= $detected
            ? "Use these — do not introduce alternative tools:\n\n" . implode("\n", $detected) . "\n"
            : "No quality tooling detected in composer.json.\n";

        if ($snapshot->composerScripts) {
            $out .= "\n## Composer scripts\n\n";
            foreach ($snapshot->composerScripts as $name => $script) {
                $out .= "- `composer {$name}`\n";
            }
        }

        return $out;
    }
}

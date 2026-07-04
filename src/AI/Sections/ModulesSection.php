<?php

namespace Jackwander\ModuleMaker\AI\Sections;

use Jackwander\ModuleMaker\AI\Contracts\Section;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class ModulesSection implements Section
{
    public function key(): string
    {
        return 'modules';
    }

    public function title(): string
    {
        return 'Module Inventory';
    }

    public function render(ProjectSnapshot $snapshot, Depth $depth): string
    {
        $out = "# {$this->title()}\n\n";

        if (! $snapshot->modules) {
            return $out . "No modules exist yet. Create the first one with `php artisan jw:make-module {Name}`.\n";
        }

        $out .= "Existing modules under `{$snapshot->modulesPath}`. Before creating anything, check whether a module already covers the domain — never duplicate services or models across modules.\n\n";
        $out .= "| Module | Components present |\n| --- | --- |\n";

        foreach ($snapshot->modules as $name => $components) {
            $out .= "| {$name} | " . ($components ? implode(', ', $components) : '—') . " |\n";
        }

        $out .= "\nLive detail per module: MCP tool `module_structure {\"module\": \"Name\"}`.\n";

        return $out;
    }
}

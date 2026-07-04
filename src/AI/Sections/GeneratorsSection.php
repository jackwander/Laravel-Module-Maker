<?php

namespace Jackwander\ModuleMaker\AI\Sections;

use Jackwander\ModuleMaker\AI\CommandCatalog;
use Jackwander\ModuleMaker\AI\Contracts\Section;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class GeneratorsSection implements Section
{
    public function __construct(protected CommandCatalog $catalog)
    {
    }

    public function key(): string
    {
        return 'generators';
    }

    public function title(): string
    {
        return 'Artisan Generators (jw:*)';
    }

    public function render(ProjectSnapshot $snapshot, Depth $depth): string
    {
        $out = "# {$this->title()}\n\n";
        $out .= "These commands are the ONLY sanctioned way to create files inside modules. ";
        $out .= "They enforce naming, namespaces, and parent classes automatically.\n\n";
        $out .= $this->catalog->markdownTable() . "\n";

        if ($depth === Depth::Compressed) {
            return $out;
        }

        foreach ($this->catalog->all() as $entry) {
            $out .= "\n## `{$entry['name']}`\n\n{$entry['description']}\n\n";
            $out .= "```shell\nphp artisan " . $this->catalog->usage($entry) . "\n```\n";

            if ($entry['options']) {
                $out .= "\nOptions:\n";
                foreach ($entry['options'] as $option) {
                    $shortcut = $option['shortcut'] ? "`-{$option['shortcut']}` / " : '';
                    $out .= "- {$shortcut}`--{$option['name']}`" . ($option['description'] ? " — {$option['description']}" : '') . "\n";
                }
            }

            if ($entry['creates']) {
                $out .= "\nCreates:\n";
                foreach ($entry['creates'] as $file) {
                    $out .= "- `{$file}`\n";
                }
            }
        }

        return $out;
    }
}

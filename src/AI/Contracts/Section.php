<?php

namespace Jackwander\ModuleMaker\AI\Contracts;

use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

interface Section
{
    /** Kebab-case key; also the output file name ({key}.md). */
    public function key(): string;

    public function title(): string;

    public function render(ProjectSnapshot $snapshot, Depth $depth): string;
}

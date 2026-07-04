<?php

namespace Jackwander\ModuleMaker\AI\Contracts;

use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

interface PlatformAdapter
{
    /** Unique key used in config and --platforms (e.g. "cursor"). */
    public function name(): string;

    /** Human label for the interactive prompt. */
    public function label(): string;

    /** True when traces of this platform already exist in the project. */
    public function detected(): bool;

    /** Relative paths this adapter will write (for --dry-run output). */
    public function plannedFiles(ProjectSnapshot $snapshot): array;

    /** Write entry files (+ MCP registration). Returns path => status. */
    public function write(ProjectSnapshot $snapshot, Depth $depth, bool $registerMcp): array;
}

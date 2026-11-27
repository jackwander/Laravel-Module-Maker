<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Jackwander\ModuleMaker\ModuleStatus;

class ModuleDisable extends Command
{
    protected $signature = 'jw:module:disable {name}';
    protected $description = 'Disable a module: its service provider and routes are no longer loaded';

    /** The state this command writes; ModuleEnable flips it. */
    protected bool $enable = false;

    public function handle()
    {
        $moduleName = $this->argument('name');
        $state = $this->enable ? 'enabled' : 'disabled';

        if (! in_array($moduleName, ModuleStatus::modules(), true)) {
            $this->error("$moduleName not found.");
            return 1;
        }

        if (! $this->enable && $moduleName === ModuleStatus::ALWAYS_ENABLED) {
            $this->error("The {$moduleName} module cannot be disabled.");
            return 1;
        }

        $statuses = ModuleStatus::read();

        if ($statuses === null) {
            $this->error('Status file ' . ModuleStatus::path() . ' is not valid JSON. Fix or delete it first.');
            return 1;
        }

        if (($statuses[$moduleName] ?? null) === $this->enable) {
            $this->info("Module {$moduleName} is already {$state}.");
            return 0;
        }

        ModuleStatus::set($moduleName, $this->enable);

        $this->info("Module {$moduleName} {$state}.");
        $this->line('If routes or config are cached, rebuild them: php artisan optimize:clear (or re-run route:cache).');

        return 0;
    }
}

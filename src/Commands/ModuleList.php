<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Jackwander\ModuleMaker\ModuleStatus;

class ModuleList extends Command
{
    protected $signature = 'jw:module:list';
    protected $description = 'List every module with its enabled/disabled status';

    public function handle()
    {
        $modules = ModuleStatus::modules();

        if (! $modules) {
            $this->info('No modules found.');
            return 0;
        }

        $statuses = ModuleStatus::read();

        if ($statuses === null) {
            $this->warn('Status file ' . ModuleStatus::path() . ' is not valid JSON; treating every module as enabled.');
        }

        $this->table(['Module', 'Status'], array_map(
            fn ($module) => [$module, ModuleStatus::isEnabled($module, $statuses ?? []) ? 'enabled' : 'disabled'],
            $modules
        ));

        return 0;
    }
}

<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\ModuleStatus;

class ModuleCheck extends Command
{
    protected $signature = 'jw:check';
    protected $description = 'Check the health and configuration of the Module system';

    public function handle()
    {
        $this->info("Checking Module Maker System:");

        $modulesPath = config('module-maker.paths.modules', app_path('Modules'));
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $checkCount = 0;

        // 1. Check Directory
        if (File::exists($modulesPath)) {
            $this->components->twoColumnDetail('Modules Directory', '<fg=green>Found</>');
            $checkCount++;
        } else {
            $this->components->twoColumnDetail('Modules Directory', '<fg=yellow>Missing (Run jw:make-module to create)</>');
        }

        // 2. Check Autoloading
        // Check if the Package Service Provider is registered in Laravel
        $packageLoaded = $this->laravel->providerIsLoaded(\Jackwander\ModuleMaker\ModuleServiceProvider::class);

        $this->components->twoColumnDetail(
            'Package Status',
            $packageLoaded ? '<fg=green>Active</>' : '<fg=red>Inactive</>'
        );

        // 3. List Detected Modules
        $modules = ModuleStatus::modules();
        if (count($modules) > 0) {
            $this->newLine();
            $this->info("Detected Modules:");
            $statuses = ModuleStatus::read() ?? [];
            foreach ($modules as $name) {
                if (! ModuleStatus::isEnabled($name, $statuses)) {
                    $this->components->twoColumnDetail($name, '<fg=yellow>Disabled</>');
                    continue;
                }
                $hasProvider = class_exists("{$baseNamespace}\\{$name}\\Providers\\{$name}ServiceProvider");
                $this->components->twoColumnDetail($name, $hasProvider ? '<fg=green>Loaded</>' : '<fg=gray>No Provider Found</>');
            }
        }

        $this->newLine();
        $this->showSeederHealth($modulesPath, $baseNamespace);
        $this->newLine();
        $this->info("Health check complete.");
    }

    protected function showSeederHealth($modulesPath, $baseNamespace)
    {
        $this->info("\n🌱 Modular Seeder Discovery:");
        $foundSeeders = [];

        if (!File::exists($modulesPath)) return;

        foreach (ModuleStatus::modules() as $moduleName) {
            $seederPath = "{$modulesPath}/{$moduleName}/Database/Seeders";

            if (File::exists($seederPath)) {
                $files = File::files($seederPath);
                foreach ($files as $file) {
                    $className = str_replace('.php', '', $file->getFilename());
                    $foundSeeders[] = "{$baseNamespace}\\{$moduleName}\\Database\\Seeders\\{$className}";
                }
            }
        }

        if (empty($foundSeeders)) {
            $this->line("   No modular seeders found.");
            return;
        }

        $this->line("   Copy this into your database/seeders/DatabaseSeeder.php:");
        $this->newLine();

        $snippet = "        \$this->call([\n";
        foreach ($foundSeeders as $seeder) {
            $snippet .= "            \\{$seeder}::class,\n";
        }
        $snippet .= "        ]);";

        $this->question($snippet);
    }
}

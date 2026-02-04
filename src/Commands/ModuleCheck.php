<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModuleCheck extends Command
{
    protected $signature = 'jw:check';
    protected $description = 'Check the health and configuration of the Module system';

    public function handle()
    {
        $this->info("Checking Module Maker System:");

        $modulesPath = app_path('Modules');
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

        // Check if any modules exist, and if so, if their providers are being registered
        $modulesPath = app_path('Modules');
        $modules = File::exists($modulesPath) ? File::directories($modulesPath) : [];

        $this->components->twoColumnDetail(
            'Package Status',
            $packageLoaded ? '<fg=green>Active</>' : '<fg=red>Inactive</>'
        );

        // 3. List Detected Modules
        $modules = File::exists($modulesPath) ? File::directories($modulesPath) : [];
        if (count($modules) > 0) {
            $this->newLine();
            $this->info("Detected Modules:");
            foreach ($modules as $path) {
                $name = basename($path);
                $hasProvider = class_exists("App\\Modules\\{$name}\\Providers\\{$name}ServiceProvider");
                $this->components->twoColumnDetail($name, $hasProvider ? '<fg=green>Loaded</>' : '<fg=gray>No Provider Found</>');
            }
        }

        $this->newLine();
        $this->info("Health check complete.");
    }
}

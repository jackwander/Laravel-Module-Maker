<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

    protected $signature = 'jw:make-module {name}';
    protected $description = 'Create a new module';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $moduleName = $this->argument('name');
        $modulePath = "{$basePath}/{$moduleName}";

        if (!$this->files->exists($basePath)) {
            $this->files->makeDirectory($basePath, 0755, true);
        }

        if ($this->files->exists($modulePath)) {
            $this->info("Module {$moduleName} already exists!");
            return 1;
        }

        // Generate Module scaffolding
        $this->createConfigFile($moduleName);
        $this->createDatabaseDirectory($moduleName);
        $this->createRoutesDirectory($moduleName);
        $this->createRouteFile($moduleName);
        $this->createServiceProviderFile($moduleName);

        // Generate Components via Sub-Commands to keep it DRY
        $this->info("Generating components for module {$moduleName}...");
        
        Artisan::call('jw:make-model', [
            'name' => $moduleName,
            '--module' => $moduleName,
            '--all' => true, // Also generates migration, controller, and service!
        ]);

        $this->info("Artisan output: " . Artisan::output());
        $this->info("Module {$moduleName} created successfully.");
    }

    protected function createDatabaseDirectory($moduleName)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $directoryPath = "{$basePath}/{$moduleName}/Database";

        $databaseFolders = [
            'Migrations',
            'Seeders',
            'Factories',
        ];

        foreach ($databaseFolders as $folder) {
            $folderPath = "{$directoryPath}/{$folder}";
            if (!$this->files->exists($folderPath)) {
                $this->files->makeDirectory($folderPath, 0755, true);
            }
        }
    }

    protected function createServiceProviderFile($moduleName)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}/Providers";
        $providerName = "{$moduleName}ServiceProvider";
        $providerPath = "{$modulePath}/{$providerName}.php";

        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $namespace = "{$baseNamespace}\\{$moduleName}\\Providers";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        if (!$this->files->exists($providerPath)) {
            $providerContent = $this->getStubContent('provider', [
                'namespace' => $namespace,
                'class' => $providerName,
                'moduleName' => $moduleName,
                'moduleNameLower' => strtolower($moduleName),
            ]);

            $this->files->put($providerPath, $providerContent);
            $this->info("ServiceProvider file created successfully.");
        }
    }

    protected function createRoutesDirectory($moduleName)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $directoryPath = "{$basePath}/{$moduleName}/Routes";
        if (!$this->files->exists($directoryPath)) {
            $this->files->makeDirectory($directoryPath, 0755, true);
        }
    }

    protected function createRouteFile($moduleName)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}/Routes";
        $routeFilePath = "{$modulePath}/api.php";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $controllerName = Str::plural($moduleName) . 'Controller';
        $controllerNamespace = "{$baseNamespace}\\{$moduleName}\\Controllers\\{$controllerName}";

        if (!$this->files->exists($routeFilePath)) {
            $routePrefix = Str::plural(strtolower($moduleName));
            
            $routeContent = $this->getStubContent('route', [
                'controllerNamespace' => $controllerNamespace,
                'controllerName' => $controllerName,
                'routePrefix' => $routePrefix,
            ]);

            $this->files->put($routeFilePath, $routeContent);
            $this->info("Route file created successfully.");
        }
    }

    protected function createConfigFile($moduleName)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        $configPath = "{$modulePath}/config.php";
        if (!$this->files->exists($configPath)) {
            $configContent = $this->getStubContent('config', [
                'moduleName' => $moduleName,
            ]);
            
            $this->files->put($configPath, $configContent);
            $this->info("Config file created successfully.");
        }
    }
}

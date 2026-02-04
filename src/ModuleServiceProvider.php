<?php

namespace Jackwander\ModuleMaker;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Jackwander\ModuleMaker\Commands\{
    MakeController, MakeMigration, MakeModel,
    MakeModule, MakeService, ModuleCheck
};

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCommands();

        $modulesPath = app_path('Modules');

        if (File::exists($modulesPath)) {
            // 1. Get the ACTIVE Composer loader instance
            $loaders = spl_autoload_functions();
            foreach ($loaders as $loader) {
                if (is_array($loader) && $loader[0] instanceof \Composer\Autoload\ClassLoader) {
                    $loader[0]->addPsr4('Modules\\', $modulesPath);
                    break;
                }
            }

            // 2. Now register providers and routes
            $this->registerModuleProviders();
            $this->registerModuleRoutes();
        }
    }

    protected function registerCommands(): void
    {
        $this->commands([
            MakeModule::class,
            MakeMigration::class,
            MakeModel::class,
            MakeService::class,
            MakeController::class,
            ModuleCheck::class,
        ]);
    }

    protected function registerModuleProviders(): void
    {
        $modulesPath = app_path('Modules');
        $moduleDirectories = File::directories($modulesPath);

        foreach ($moduleDirectories as $directory) {
            $moduleName = basename($directory);
            $providerClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    protected function registerModuleRoutes(): void
    {
        $modulesPath = app_path('Modules');
        $moduleDirectories = File::directories($modulesPath);

        foreach ($moduleDirectories as $directory) {
            $routeFile = "{$directory}/Routes/api.php";

            if (File::exists($routeFile)) {
                Route::prefix('api/v1')
                    ->middleware('api')
                    ->group($routeFile);
            }
        }
    }
}

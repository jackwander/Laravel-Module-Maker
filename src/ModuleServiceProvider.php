<?php

namespace Jackwander\ModuleMaker;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Jackwander\ModuleMaker\Commands\{MakeController,
  MakeFactory,
  MakeMigration,
  MakeModel,
  MakeModule,
  MakeService,
  MakeResource,
  MakeRequest,
  MakeJob,
  MakeEvent,
  MakeListener,
  MakePolicy,
  MakeRule,
  MakeObserver,
  ModuleCheck,
  MakeSeeder};

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
      Factory::guessFactoryNamesUsing(function (string $modelName) {
          // If the model is in our Modules namespace...
          if (Str::startsWith($modelName, 'App\\Modules\\')) {
              // Convert: App\Modules\Employees\Models\Employee
              // To: App\Modules\Employees\Database\Factories\EmployeeFactory
              return str_replace(
                  ['\\Models\\', 'Models\\'],
                  '\\Database\\Factories\\',
                  $modelName
              ) . 'Factory';
          }

          // Fallback to default Laravel behavior
          return 'Database\\Factories\\' . class_basename($modelName) . 'Factory';
      });

      $configPath = dirname(__DIR__) . '/config/module-maker.php';
      $stubsPath = dirname(__DIR__) . '/stubs';

      $this->publishes([
          $configPath => config_path('module-maker.php'),
      ], 'module-maker-config');

      $this->publishes([
          $stubsPath => base_path('stubs/vendor/module-maker'),
      ], 'module-maker-stubs');
    }
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/module-maker.php', 'module-maker'
        );

        $this->registerCommands();

        if (file_exists(config('module-maker.paths.modules', app_path('Modules')))) {
            $this->registerModuleProviders();
        }
    }

    protected function registerCommands(): void
    {
        $this->commands([
            MakeModule::class,
            MakeMigration::class,
            MakeModel::class,
            MakeService::class,
            MakeResource::class,
            MakeRequest::class,
            MakeJob::class,
            MakeEvent::class,
            MakeListener::class,
            MakePolicy::class,
            MakeRule::class,
            MakeObserver::class,
            MakeController::class,
            ModuleCheck::class,
            MakeSeeder::class,
            MakeFactory::class,
        ]);
    }

    protected function registerModuleProviders(): void
    {
        $modulesPath = config('module-maker.paths.modules', app_path('Modules'));

        // Cache the discovered modules for production performance.
        $modules = $this->app->environment('local', 'testing')
            ? array_map('basename', File::directories($modulesPath))
            : cache()->rememberForever('module-maker.modules', function () use ($modulesPath) {
                return array_map('basename', File::directories($modulesPath));
            });

        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');

        foreach ($modules as $moduleName) {
            $providerClass = "{$baseNamespace}\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }
}

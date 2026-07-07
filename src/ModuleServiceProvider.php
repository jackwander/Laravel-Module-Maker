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
  MakeSeeder,
  Init,
  AiInit,
  McpServe};

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
      ], ['module-maker-config', 'config']);

      $this->publishes([
          dirname(__DIR__) . '/config/module-ai.php' => config_path('module-ai.php'),
      ], ['module-ai-config', 'config']);

      $this->publishes([
          $stubsPath => base_path('stubs/vendor/module-maker'),
      ], 'module-maker-stubs');

      // Package routes fallback
      if (File::exists(__DIR__ . '/../routes/api.php')) {
          $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
      }

      $this->bootModuleRoutes();
    }
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/module-maker.php', 'module-maker'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/module-ai.php', 'module-ai'
        );

        $this->app->singleton(\Jackwander\ModuleMaker\AI\AiPlatformRegistry::class);

        $this->registerCommands();

        // Discovery caches the module list via the cache service, which is not
        // yet bound while providers are registering — calling it here throws
        // "Target class [cache] does not exist". Defer to the container's
        // booting phase: cache is available by then, and providers registered
        // in a booting callback are still picked up by the normal boot loop,
        // so their own boot() (migrations, etc.) still fires.
        $this->app->booting(function () {
            $this->registerModuleProviders();
        });
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
            Init::class,
            AiInit::class,
            McpServe::class,
        ]);
    }

    protected function registerModuleProviders(): void
    {
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');

        foreach ($this->discoverModules() as $moduleName) {
            $providerClass = "{$baseNamespace}\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * Resolve the list of module directory names.
     *
     * Cached forever outside local/testing for production performance; the
     * cache service must be bound before this runs (see register()).
     *
     * @return array<int, string>
     */
    protected function discoverModules(): array
    {
        $modulesPath = config('module-maker.paths.modules', app_path('Modules'));

        if (! File::isDirectory($modulesPath)) {
            return [];
        }

        if ($this->app->environment('local', 'testing')) {
            return array_map('basename', File::directories($modulesPath));
        }

        return cache()->rememberForever('module-maker.modules', function () use ($modulesPath) {
            return array_map('basename', File::directories($modulesPath));
        });
    }

    /**
     * Bootstrap routes for all modules.
     */
    protected function bootModuleRoutes(): void
    {
        $modulesPath = config('module-maker.paths.modules', app_path('Modules'));

        foreach ($this->discoverModules() as $moduleName) {
            $routeFile = "{$modulesPath}/{$moduleName}/Routes/api.php";

            if (File::exists($routeFile)) {
                Route::prefix(config('module-maker.paths.api_prefix', 'api/v1'))
                    ->middleware('api')
                    ->group(function () use ($routeFile) {
                        $this->loadRoutesFrom($routeFile);
                    });
            }
        }
    }
}

<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeModule extends Command
{
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
    $modulesPath = app_path('Modules');

    // Check if Modules directory exists and create it if not
    if (!$this->files->exists($modulesPath)) {
        $this->files->makeDirectory($modulesPath, 0755, true);
        $this->info("Directory {$modulesPath} created successfully.");
    }

    $moduleName = $this->argument('name');
    $modulePath = app_path("Modules/{$moduleName}");

    if ($this->files->exists($modulePath)) {
        $this->info("Module {$moduleName} already exists!");
    }

    $this->createConfigFile($modulePath);
    $this->createDatabaseDirectory($moduleName);
    $this->createModelsDirectory($moduleName);
    $this->createServicesDirectory($moduleName);
    $this->createServiceFile($moduleName);
    $this->createServiceProviderFile($moduleName);
    $this->createControllersDirectory($moduleName);
    $this->createControllerFile($moduleName);
    $this->createRoutesDirectory($moduleName);
    $this->createRouteFile($moduleName);

    $this->info("Module {$moduleName} created successfully.");
  }

  protected function createControllersDirectory($moduleName)
  {
    $directoryPath = "app/Modules/{$moduleName}/Controllers";
    if (!$this->files->exists($directoryPath)) {
      $this->files->makeDirectory($directoryPath, 0755, true);
      $this->info("Directory {$directoryPath} created successfully.");
    } else {
      $this->info("Directory {$directoryPath} already exists.");
    }
  }

  protected function createControllerFile($moduleName)
  {
      $modulePath = "app/Modules/{$moduleName}/Controllers";

      // Ensure the specific module directory exists
      if (!$this->files->exists($modulePath)) {
          $this->files->makeDirectory($modulePath, 0755, true);
          $this->info("Directory {$modulePath} created successfully.");
      }

      // Define the controller name by removing the trailing 's' and appending 'Controller'
      $controllerName = rtrim($moduleName, 's') . 'Controller';
      $controllerPath = "{$modulePath}/{$controllerName}.php";
      $variableModel = "$".strtolower($moduleName);
      $this_variableModel = '$this->'.strtolower($moduleName);
      $ucmodel = "'".ucwords($moduleName)."'";

      // Check if the controller file already exists
      if (!$this->files->exists($controllerPath)) {
          $controllerContent = "<?php\n\nnamespace Modules\\{$moduleName}\\Controllers;\n\nuse Jackwander\ModuleMaker\Resources\BaseApiController;\n\nclass {$controllerName} extends BaseApiController\n{\n  public function __construct(\n    protected {$moduleName}Service {$variableModel},\n  ){\n    parent::__construct({$this_variableModel}, {$ucmodel});\n  }\n}\n";
          $this->files->put($controllerPath, $controllerContent);
          $this->info("Controller file {$controllerPath} created successfully.");
      } else {
          $this->info("Controller file {$controllerPath} already exists.");
      }
  }


  protected function createDatabaseDirectory($moduleName)
  {
    $directoryPath = "app/Modules/{$moduleName}/Database";
    if (!$this->files->exists($directoryPath)) {
        $this->files->makeDirectory($directoryPath, 0755, true);
        $this->info("Directory {$directoryPath} created successfully.");
    } else {
        $this->info("Directory {$directoryPath} already exists.");
    }

    $databaseFolders = [
        'Migrations',
        'Seeders',
        'Factories',
    ];

    foreach ($databaseFolders as $folder) {
        $folderPath = "{$directoryPath}/{$folder}";
        if (!$this->files->exists($folderPath)) {
            $this->files->makeDirectory($folderPath, 0755, true);
            $this->info("Directory {$folderPath} created successfully.");
        } else {
            $this->info("Directory {$folderPath} already exists.");
        }
    }
    $migrationName = 'create_' . strtolower(Str::plural($this->argument('name'))) . '_table';
    $migrationPath = "{$directoryPath}/Migrations";

    // Run the make:migration command
    Artisan::call('make:migration', [
        'name' => $migrationName,
        '--path' => $migrationPath,
    ]);

    $migrationFileName = $migrationName . '.php';
    $this->info("Artisan output: " . Artisan::output());
  }


  protected function createModelsDirectory($moduleName)
  {
    $directoryPath = "app/Modules/{$moduleName}/Database";
    if (!$this->files->exists($directoryPath)) {
      $this->files->makeDirectory($directoryPath, 0755, true);
      $this->info("Directory {$directoryPath} created successfully.");
    } else {
      $this->info("Directory {$directoryPath} already exists.");
    }
    $this->createModelFile($directoryPath, $moduleName);
  }

  protected function createModelFile($directoryPath, $moduleName)
  {
    $modulePath = "app/Modules/{$moduleName}/Models";
    // Ensure the specific module directory exists
    if (!$this->files->exists($modulePath)) {
        $this->files->makeDirectory($modulePath, 0755, true);
        $this->info("Directory {$modulePath} created successfully.");
    }

    $modelName = Str::singular($moduleName); // Remove the trailing 's' from the module name for singular model name
    $modelPath = "{$modulePath}/{$modelName}.php";

    $table_name = '$table = ' . '"'. Str::plural(strtolower($moduleName)) . '"';
    if (!$this->files->exists($modelPath)) {
      $modelContent = "<?php\n\nnamespace Modules\\{$moduleName}\Models;\n\nuse Jackwander\ModuleMaker\Resources\BaseModel;\nuse Illuminate\Database\Eloquent\Concerns\HasUuids;\nuse Illuminate\Database\Eloquent\SoftDeletes;\nclass {$modelName} extends BaseModel\n{\n  use SoftDeletes, HasUuids;\n\n  protected {$table_name}\n\n  protected \$fillable = [\n  ];\n\n  protected \$keyType = 'string';\n\n  public \$incrementing = false;\n}\n\n";
      $this->files->put($modelPath, $modelContent);
      $this->info("Model file {$modelPath} created successfully.");
    } else {
      $this->info("Model file {$modelPath} already exists.");
    }
  }

  protected function createServiceProviderFile($moduleName)
  {
    $providerName = "{$moduleName}ServiceProvider";
    $modulePath = "app/Modules/{$moduleName}/Providers";
    $providerPath = "app/Modules/{$moduleName}/Providers/{$providerName}.php";

    // Ensure the specific module directory exists
    if (!$this->files->exists($modulePath)) {
        $this->files->makeDirectory($modulePath, 0755, true);
        $this->info("Directory {$modulePath} created successfully.");
    }

    if (!$this->files->exists($providerPath)) {
      $providerContent = "<?php\n\nnamespace Modules\\{$moduleName}\Providers;\n\nuse Illuminate\Support\ServiceProvider;\n\nclass {$providerName} extends ServiceProvider\n{\n    public function boot(): void\n    {\n        \$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');\n        \$this->mergeConfigFrom(__DIR__ . '/../config.php', strtolower('{$moduleName}'));\n    }\n}\n";
      $this->files->put($providerPath, $providerContent);
      $this->info("ServiceProvider file {$providerPath} created successfully.");
    } else {
      $this->info("ServiceProvider file {$providerPath} already exists.");
    }
  }

  protected function createRoutesDirectory($moduleName)
  {
    $directoryPath = "app/Modules/{$moduleName}/Routes";
    if (!$this->files->exists($directoryPath)) {
      $this->files->makeDirectory($directoryPath, 0755, true);
      $this->info("Directory {$directoryPath} created successfully.");
    } else {
      $this->info("Directory {$directoryPath} already exists.");
    }
  }

protected function createRouteFile($moduleName)
{
    $routeFileName = "api.php";
    $modulePath = "app/Modules/{$moduleName}/Routes";
    $routeFilePath = "{$modulePath}/{$routeFileName}";

    // Ensure the Routes directory exists
    if (!$this->files->exists($modulePath)) {
        $this->files->makeDirectory($modulePath, 0755, true);
        $this->info("Directory {$modulePath} created successfully.");
    }

    // Check if the route file already exists
    if (!$this->files->exists($routeFilePath)) {
        $controllerName = rtrim($moduleName, 's') . 'Controller';
        $namespace = "Modules\\{$moduleName}\\Controllers\\{$controllerName}";

        // Generate route content using the structure you provided
        $routeContent = "<?php\n\n";
        $routeContent .= "use {$namespace};\n\n";
        $routeContent .= "Route::controller({$controllerName}::class)->group(function () {\n";
        $routeContent .= "    Route::group(['prefix' => '" . Str::plural(strtolower($moduleName)) . "'], function() {\n";
        $routeContent .= "        Route::get('/all', 'all');\n";
        $routeContent .= "    });\n";
        $routeContent .= "})->middleware('auth:api');\n\n";
        $routeContent .= "Route::apiResource('" . Str::plural(strtolower($moduleName)) . "', {$controllerName}::class)->middleware('auth:api');\n";

        $this->files->put($routeFilePath, $routeContent);
        $this->info("Route file {$routeFilePath} created successfully.");
    } else {
        $this->info("Route file {$routeFilePath} already exists.");
    }
}


  protected function createServicesDirectory($moduleName)
  {
    $directoryPath = "app/Modules/{$moduleName}/Services";
    if (!$this->files->exists($directoryPath)) {
      $this->files->makeDirectory($directoryPath, 0755, true);
      $this->info("Directory {$directoryPath} created successfully.");
    } else {
      $this->info("Directory {$directoryPath} already exists.");
    }
  }

  protected function createServiceFile($moduleName)
  {
      $modulePath = "app/Modules/{$moduleName}/Services";
      $mainmodulePath = "Modules\\{$moduleName}";

      // Ensure the specific module directory exists
      if (!$this->files->exists($modulePath)) {
          $this->files->makeDirectory($modulePath, 0755, true);
          $this->info("Directory {$modulePath} created successfully.");
      }

      // Define the controller name by removing the trailing 's' and appending 'Controller'
      $serviceFileName = Str::singular($moduleName) . 'Service';
      $servicePath = "{$modulePath}/{$serviceFileName}.php";
      $variableModel = "$".strtolower($moduleName);
      $this_variableModel = '$this->'.strtolower($moduleName);

      // Check if the controller file already exists
      if (!$this->files->exists($servicePath)) {
          $serviceContent = "<?php\n\nnamespace Modules\\{$moduleName}\\Services;\n\nuse Jackwander\ModuleMaker\Resources\BaseService;\nuse {$mainmodulePath}\Models\\{$moduleName};\n\nclass {$serviceFileName} extends BaseService\n{\n  public function __construct(\n    protected {$moduleName} {$variableModel},\n  ){\n    parent::__construct({$this_variableModel});\n  }\n}\n";
          $this->files->put($servicePath, $serviceContent);
          $this->info("Service file {$servicePath} created successfully.");
      } else {
          $this->info("Service file {$servicePath} already exists.");
      }
  }


  protected function createConfigFile($modulePath)
  {
      // Ensure the specific module directory exists
      if (!$this->files->exists($modulePath)) {
          $this->files->makeDirectory($modulePath, 0755, true);
          $this->info("Directory {$modulePath} created successfully.");
      }

      $configPath = "{$modulePath}/config.php";
      if (!$this->files->exists($configPath)) {
          $configContent = "<?php\n\nreturn [\n\n];\n";
          $this->files->put($configPath, $configContent);
          $this->info("File {$configPath} created successfully.");
      } else {
          $this->info("File {$configPath} already exists.");
      }
  }
}

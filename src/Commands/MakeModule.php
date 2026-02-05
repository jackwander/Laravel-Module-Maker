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

      // 1. Get Base Class from Config (Defaulting to BaseApiController)
      $baseControllerFullClass = config(
          'module-maker.base_classes.controller',
          'Jackwander\ModuleMaker\Resources\BaseApiController'
      );
      $baseControllerShortName = class_basename($baseControllerFullClass);

      // Ensure the specific module directory exists
      if (!$this->files->exists($modulePath)) {
          $this->files->makeDirectory($modulePath, 0755, true);
          $this->info("Directory {$modulePath} created successfully.");
      }

      // 2. Derive Names from Module Name
      // Controller Name: Plural (e.g., Person -> PeopleController)
      $controllerName = Str::plural($moduleName) . 'Controller';
      $controllerPath = "{$modulePath}/{$controllerName}.php";

      // Service Name: Singular (e.g., Person -> PersonService)
      $serviceName = Str::singular($moduleName) . 'Service';

      // Variable Naming
      // Force singular for the variable to match standard injection patterns (e.g., $person)
      $cleanName = Str::singular($moduleName);
      $variableModel = "$" . strtolower(Str::snake($cleanName));
      $thisVariableModel = '$this->' . strtolower(Str::snake($cleanName));

      // Model Name string passed to parent (e.g., 'Person')
      $ucModelName = ucwords($cleanName);

      if (!$this->files->exists($controllerPath)) {
          $controllerContent = <<<EOT
  <?php
  
  namespace App\Modules\\{$moduleName}\Controllers;
  
  use {$baseControllerFullClass};
  use App\Modules\\{$moduleName}\Services\\{$serviceName};
  
  class {$controllerName} extends {$baseControllerShortName}
  {
      public function __construct(
          protected {$serviceName} {$variableModel}
      ) {
          parent::__construct({$thisVariableModel}, '{$ucModelName}');
      }
  }
  EOT;

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
    $migrationName = 'create_' . strtolower(Str::plural(Str::snake($this->argument('name')))) . '_table';

    // Run the make:migration command
    Artisan::call('jw:make-migration', [
        'name' => $migrationName,
        '--module' => $moduleName,
        '--create' => Str::snake(Str::plural($this->argument('name')))
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
      // Note: $directoryPath is passed but seemingly unused in your logic;
      // we focus on $modulePath for the Models directory.
      $modulePath = "app/Modules/{$moduleName}/Models";

      // 1. Get Base Class from Config (Defaulting to BaseModel)
      $baseModelFullClass = config(
          'module-maker.base_classes.model',
          'Jackwander\ModuleMaker\Resources\BaseModel'
      );
      $baseModelShortName = class_basename($baseModelFullClass);

      // Ensure the specific module directory exists
      if (!$this->files->exists($modulePath)) {
          $this->files->makeDirectory($modulePath, 0755, true);
          $this->info("Directory {$modulePath} created successfully.");
      }

      // 2. Derive Names
      // Model Name: Singular (e.g., Persons -> Person)
      $modelName = Str::singular($moduleName);
      $modelPath = "{$modulePath}/{$modelName}.php";

      // Table Name: Snake case and plural (e.g., Person -> persons, UserProfile -> user_profiles)
      $tableName = strtolower(Str::plural(Str::snake($moduleName)));

      if (!$this->files->exists($modelPath)) {
          // Clean Heredoc Syntax
          $modelContent = <<<EOT
  <?php
  
  namespace App\Modules\\{$moduleName}\Models;
  
  use {$baseModelFullClass};
  use Illuminate\Database\Eloquent\Concerns\HasUuids;
  use Illuminate\Database\Eloquent\SoftDeletes;
  
  class {$modelName} extends {$baseModelShortName}
  {
      use SoftDeletes, HasUuids;
  
      protected \$table = '{$tableName}';
  
      protected \$fillable = [
          //
      ];
  
      protected \$keyType = 'string';
  
      public \$incrementing = false;
  }
  EOT;

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
      $providerContent = "<?php\n\nnamespace App\\Modules\\{$moduleName}\Providers;\n\nuse Illuminate\Support\ServiceProvider;\n\nclass {$providerName} extends ServiceProvider\n{\n    public function boot(): void\n    {\n        \$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');\n        \$this->mergeConfigFrom(__DIR__ . '/../config.php', strtolower('{$moduleName}'));\n    }\n}\n";
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
        $controllerName = Str::plural($moduleName). 'Controller';
        $namespace = "App\\Modules\\{$moduleName}\\Controllers\\{$controllerName}";

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
    $mainModulePath = "App\\Modules\\{$moduleName}"; // Corrected to App\Modules

    // 1. Get Base Class from Config (Defaulting to BaseService)
    $baseServiceFullClass = config(
        'module-maker.base_classes.service',
        'Jackwander\ModuleMaker\Resources\BaseService'
    );
    $baseServiceShortName = class_basename($baseServiceFullClass);

    // Ensure the specific module directory exists
    if (!$this->files->exists($modulePath)) {
        $this->files->makeDirectory($modulePath, 0755, true);
        $this->info("Directory {$modulePath} created successfully.");
    }

    // 2. Derive Names
    $serviceFileName = Str::singular($moduleName) . 'Service';
    $servicePath = "{$modulePath}/{$serviceFileName}.php";

    $modelName = Str::singular($moduleName);

    // Variable Naming (Force singular)
    $variableModel = "$" . strtolower(Str::snake($modelName));
    $thisVariableModel = '$this->' . strtolower(Str::snake($modelName));

    if (!$this->files->exists($servicePath)) {
        // Clean Heredoc Syntax
        $serviceContent = <<<EOT
<?php

namespace App\Modules\\{$moduleName}\Services;

use {$baseServiceFullClass};
use {$mainModulePath}\Models\\{$modelName};

class {$serviceFileName} extends {$baseServiceShortName}
{
    public function __construct(
        protected {$modelName} {$variableModel}
    ) {
        parent::__construct({$thisVariableModel});
    }
}
EOT;

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

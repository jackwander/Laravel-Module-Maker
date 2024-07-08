<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

class MakeModule extends Command
{
  protected $signature = 'make:module {name}';
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
    $this->createDatabaseDirectory($modulePath);
    $this->createModelsDirectory($modulePath, $moduleName)
    $this->createServicesDirectory($modulePath);
    $this->createProvidersDirectory($modulePath, $moduleName);
    $this->createControllersDirectory($modulePath);
    $this->createRoutesDirectory($modulePath);

    $this->info("Module {$moduleName} created successfully.");
  }

  protected function createControllersDirectory($modulePath)
  {
    $directoryPath = "{$modulePath}/Controllers";
    if (!$this->files->exists($directoryPath)) {
      $this->files->makeDirectory($directoryPath, 0755, true);
      $this->info("Directory {$directoryPath} created successfully.");
    } else {
      $this->info("Directory {$directoryPath} already exists.");
    }
  }

  protected function createDatabaseDirectory($modulePath)
  {
    $directoryPath = "{$modulePath}/Database";
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

    $migrationName = 'create_' . strtolower($this->argument('name')) . '_table';
    $migrationPath = "{$directoryPath}/Migrations";

    // Run the make:migration command
    Artisan::call('make:migration', [
        'name' => $migrationName,
        '--path' => $migrationPath,
    ]);

    $this->info("Migration {$migrationName} created successfully in {$migrationPath}.");
  }


  protected function createModelsDirectory($modulePath)
  {
    $directoryPath = "{$modulePath}/Models";
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
    $modelName = rtrim($moduleName, 's'); // Remove the trailing 's' from the module name for singular model name
    $modelPath = "{$directoryPath}/{$modelName}.php";
    if (!$this->files->exists($modelPath)) {
      $modelContent = "<?php\n\nnamespace Modules\\{$moduleName}\Models;\n\nuse Jackwander\ModuleMaker\Resources\Model;\n\nclass {$modelName} extends Model\n{\n    // Model content here\n}\n";
      $this->files->put($modelPath, $modelContent);
      $this->info("Model file {$modelPath} created successfully.");
    } else {
      $this->info("Model file {$modelPath} already exists.");
    }
  }
 
    protected function createServiceProviderFile($modulePath, $moduleName)
    {
      $providerName = "{$moduleName}ServiceProvider";
      $providerPath = "{$modulePath}/Providers/{$providerName}.php";

      if (!$this->files->exists($providerPath)) {
        $providerContent = "<?php\n\nnamespace Modules\\{$moduleName}\Providers;\n\nuse Illuminate\Support\ServiceProvider;\n\nclass {$providerName} extends ServiceProvider\n{\n    public function boot(): void\n    {\n        \$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');\n        \$this->mergeConfigFrom(__DIR__ . '/../config.php', strtolower('{$moduleName}'));\n    }\n}\n";
        $this->files->put($providerPath, $providerContent);
        $this->info("ServiceProvider file {$providerPath} created successfully.");
      } else {
        $this->info("ServiceProvider file {$providerPath} already exists.");
      }
    }

  protected function createRoutesDirectory($modulePath)
  {
    $directoryPath = "{$modulePath}/Routes";
    if (!$this->files->exists($directoryPath)) {
      $this->files->makeDirectory($directoryPath, 0755, true);
      $this->info("Directory {$directoryPath} created successfully.");
    } else {
      $this->info("Directory {$directoryPath} already exists.");
    }
  }

  protected function createServicesDirectory($modulePath)
  {
    $directoryPath = "{$modulePath}/Services";
    if (!$this->files->exists($directoryPath)) {
      $this->files->makeDirectory($directoryPath, 0755, true);
      $this->info("Directory {$directoryPath} created successfully.");
    } else {
      $this->info("Directory {$directoryPath} already exists.");
    }
  }


  protected function createConfigFile($modulePath)
  {
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

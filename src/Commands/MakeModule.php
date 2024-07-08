<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

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
    $moduleName = $this->argument('name');
    $modulePath = app_path("Modules/{$moduleName}");

    if ($this->files->exists($modulePath)) {
        $this->error("Module {$moduleName} already exists!");
        return;
    }

    $this->createDirectories($modulePath);
    $this->createConfigFile($modulePath);

    $this->info("Module {$moduleName} created successfully.");
  }

  protected function createDirectories($modulePath)
  {
    $directories = [
        'Controllers',
        'Database',
        'Models',
        'Providers',
        'Routes',
        'Services',
    ];

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
      $configContent = "<?php\n\nreturn [\n\n];\n";
      $this->files->put($configPath, $configContent);
  }
}

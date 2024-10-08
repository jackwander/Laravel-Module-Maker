<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeService extends Command
{
    protected $signature = 'jw:make-service {name} {--module=}';
    protected $description = 'Create a new Service file for a specific module';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $modelName = $this->argument('name');
        $moduleName = $this->option('module');

        if (!$moduleName) {
            $this->error('The --module flag is required.');
            return 1;
        }

        $modulePath = app_path("Modules/{$moduleName}/Models");
        if (!$this->files->exists($modulePath)) {
            $this->error("$moduleName not found.");
            return 1;
        }

        $this->createServiceFile($moduleName, $modelName);
        $this->info("Service {$modelName} created successfully.");
    }

  protected function createServiceFile($moduleName, $model)
  {
      $modulePath = "app/Modules/{$moduleName}/Services";
      $mainmodulePath = "Modules\\{$moduleName}";

      // Ensure the specific module directory exists
      if (!$this->files->exists($modulePath)) {
          $this->files->makeDirectory($modulePath, 0755, true);
          $this->info("Directory {$modulePath} created successfully.");
      }

      // Define the controller name by removing the trailing 's' and appending 'Service'
      $serviceFileName = Str::singular($model) . 'Service';
      $servicePath = "{$modulePath}/{$serviceFileName}.php";
      $variableModel = "$".strtolower($model);
      $this_variableModel = '$this->'.strtolower($model);
      $modelName = Str::singular($model);

      // Check if the controller file already exists
      if (!$this->files->exists($servicePath)) {
          $serviceContent = "<?php\n\nnamespace Modules\\{$moduleName}\\Services;\n\nuse Jackwander\ModuleMaker\Resources\BaseService;\nuse {$mainmodulePath}\Models\\{$modelName};\n\nclass {$serviceFileName} extends BaseService\n{\n  public function __construct(\n    protected {$modelName} {$variableModel},\n  ){\n    parent::__construct({$this_variableModel});\n  }\n}\n";
          $this->files->put($servicePath, $serviceContent);
          $this->info("Service file {$servicePath} created successfully.");
      } else {
          $this->info("Service file {$servicePath} already exists.");
      }
  }
}

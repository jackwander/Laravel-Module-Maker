<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeMigration extends Command
{
    protected $signature = 'jw:make-controller {name} {--module=}';
    protected $description = 'Create a new Controller file for a specific module';

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

        $this->createControllerFile($moduleName, $modelName);
        $this->info("Controller {$modelName} created successfully.");
    }


  protected function createControllerFile($moduleName, $model)
  {
      $modulePath = "app/Modules/{$moduleName}/Controllers";

      // Ensure the specific module directory exists
      if (!$this->files->exists($modulePath)) {
          $this->files->makeDirectory($modulePath, 0755, true);
          $this->info("Directory {$modulePath} created successfully.");
      }

      // Define the controller name by removing the trailing 's' and appending 'Controller'
      $controllerName = Str::plural($model) . 'Controller';
      $controllerPath = "{$modulePath}/{$controllerName}.php";
      $variableModel = "$".strtolower($model);
      $this_variableModel = '$this->'.strtolower($model);
      $ucmodel = "'".ucwords($model)."'";
      $serviceName = Str::singular($model).'Service';

      // Check if the controller file already exists
      if (!$this->files->exists($controllerPath)) {
          $controllerContent = "<?php\n\nnamespace Modules\\{$moduleName}\\Controllers;\n\nuse Jackwander\ModuleMaker\Resources\BaseApiController;\nuse Modules\\{$moduleName}\\Services\\{$serviceName};\n\nclass {$controllerName} extends BaseApiController\n{\n  public function __construct(\n    protected {$serviceName} {$variableModel},\n  ){\n    parent::__construct({$this_variableModel}, {$ucmodel});\n  }\n}\n";
          $this->files->put($controllerPath, $controllerContent);
          $this->info("Controller file {$controllerPath} created successfully.");
      } else {
          $this->info("Controller file {$controllerPath} already exists.");
      }
  }
}

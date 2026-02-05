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
        $mainModulePath = "App\\Modules\\{$moduleName}";

        // 1. Get the Base Class from Config (with Fallback)
        $baseServiceFullClass = config(
            'module-maker.base_classes.service',
            'Jackwander\ModuleMaker\Base\BaseService'
        );
        $baseServiceShortName = class_basename($baseServiceFullClass);

        // Ensure the specific module directory exists
        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
            $this->info("Directory {$modulePath} created successfully.");
        }

        // Define the Service name
        $serviceFileName = Str::singular($model) . 'Service';
        $servicePath = "{$modulePath}/{$serviceFileName}.php";

        // Variable formatting for the stub
        $modelName = Str::singular($model);
        $variableModel = "$" . strtolower(Str::snake($modelName));
        $thisVariableModel = '$this->' . strtolower(Str::snake($modelName));

        // Check if the file already exists
        if (!$this->files->exists($servicePath)) {
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
}

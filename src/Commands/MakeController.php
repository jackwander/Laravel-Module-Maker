<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeController extends Command
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

        // 1. Get Base Class from Config (Defaulting to BaseApiController)
        $baseControllerFullClass = config(
            'module-maker.base_classes.api_controller',
            'Jackwander\ModuleMaker\Resources\BaseApiController'
        );
        $baseControllerShortName = class_basename($baseControllerFullClass);

        // Ensure the specific module directory exists
        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
            $this->info("Directory {$modulePath} created successfully.");
        }

        // Naming Conventions
        $controllerName = Str::plural($model) . 'Controller';
        $controllerPath = "{$modulePath}/{$controllerName}.php";

        // Dependent Service Naming
        $serviceName = Str::singular($model) . 'Service';

        // Variable Formatting
        // $variableModel -> $person
        $variableModel = "$" . strtolower(Str::snake($model));

        // $thisVariableModel -> $this->person
        $thisVariableModel = '$this->' . strtolower(Str::snake($model));

        // $ucModelName -> 'Person' (Passed as string to parent)
        $ucModelName = ucwords($model);

        if (!$this->files->exists($controllerPath)) {
            // Clean Heredoc Syntax
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
}

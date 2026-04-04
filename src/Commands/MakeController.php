<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeController extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

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

        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}/Models";

        if (!$this->files->exists($modulePath)) {
            $this->error("Module models directory not found at $modulePath. Ensure you created the module properly.");
            return 1;
        }

        $this->createControllerFile($moduleName, $modelName);
        $this->info("Controller {$modelName} created successfully.");
    }


    protected function createControllerFile($moduleName, $model)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}/Controllers";
        
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $namespace = "{$baseNamespace}\\{$moduleName}\\Controllers";
        $mainModuleNamespace = "{$baseNamespace}\\{$moduleName}";

        // 1. Get Base Class from Config
        $baseControllerFullClass = config(
            'module-maker.base_classes.api_controller',
            'Jackwander\ModuleMaker\Base\BaseApiController'
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

        $variableModel = "$" . strtolower(Str::snake($model));
        $plainVariableModel = strtolower(Str::snake($model));
        $ucModelName = ucwords(str_replace('_', ' ', Str::snake($model)));

        if (!$this->files->exists($controllerPath)) {
            $controllerContent = $this->getStubContent('controller', [
                'namespace' => $namespace,
                'baseControllerFullClass' => $baseControllerFullClass,
                'mainModuleNamespace' => $mainModuleNamespace,
                'serviceName' => $serviceName,
                'class' => $controllerName,
                'baseControllerShortName' => $baseControllerShortName,
                'variableModel' => $variableModel,
                'plainVariableModel' => $plainVariableModel,
                'ucModelName' => $ucModelName,
            ]);

            $this->files->put($controllerPath, $controllerContent);
            $this->info("Controller file {$controllerPath} created successfully.");
        } else {
            $this->info("Controller file {$controllerPath} already exists.");
        }
    }
}

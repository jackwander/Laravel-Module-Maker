<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeService extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

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

        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}";

        if (!$this->files->exists($modulePath)) {
            $this->error("$moduleName not found.");
            return 1;
        }

        $this->createServiceFile($moduleName, $modelName);
        $this->info("Service {$modelName} created successfully.");
    }

    protected function createServiceFile($moduleName, $model)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}/Services";
        
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $namespace = "{$baseNamespace}\\{$moduleName}\\Services";
        $mainModuleNamespace = "{$baseNamespace}\\{$moduleName}";

        // 1. Get the Base Class from Config
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
        $plainVariableModel = strtolower(Str::snake($modelName));

        // Check if the file already exists
        if (!$this->files->exists($servicePath)) {
            $serviceContent = $this->getStubContent('service', [
                'namespace' => $namespace,
                'baseServiceFullClass' => $baseServiceFullClass,
                'baseServiceShortName' => $baseServiceShortName,
                'mainModuleNamespace' => $mainModuleNamespace,
                'modelName' => $modelName,
                'class' => $serviceFileName,
                'variableModel' => $variableModel,
                'plainVariableModel' => $plainVariableModel,
            ]);

            $this->files->put($servicePath, $serviceContent);
            $this->info("Service file {$servicePath} created successfully.");
        } else {
            $this->info("Service file {$servicePath} already exists.");
        }
    }
}

<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeResource extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

    protected $signature = 'jw:make-resource {name} {--module=}';
    protected $description = 'Create a new API Resource file for a specific module';

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
            $this->error("Module {$moduleName} not found.");
            return 1;
        }

        $this->createResourceFile($moduleName, $modelName);
        $this->info("Resource {$modelName}Resource created successfully.");
    }

    protected function createResourceFile($moduleName, $model)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}/Resources";
        
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $namespace = "{$baseNamespace}\\{$moduleName}\\Resources";

        // Ensure the specific module directory exists
        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
            $this->info("Directory {$modulePath} created successfully.");
        }

        // Define the Resource name
        $resourceFileName = Str::singular($model) . 'Resource';
        $resourcePath = "{$modulePath}/{$resourceFileName}.php";

        // Check if the file already exists
        if (!$this->files->exists($resourcePath)) {
            $resourceContent = $this->getStubContent('resource', [
                'namespace' => $namespace,
                'class' => $resourceFileName,
            ]);

            $this->files->put($resourcePath, $resourceContent);
            $this->info("Resource file {$resourcePath} created successfully.");
        } else {
            $this->info("Resource file {$resourcePath} already exists.");
        }
    }
}

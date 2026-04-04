<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeRule extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

    protected $signature = 'jw:make-rule {name} {--module=}';
    protected $description = 'Create a new Validation Rule file for a specific module';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
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

        $this->createFile($moduleName, $name);
        return 0;
    }

    protected function createFile($moduleName, $name)
    {
        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $modulePath = "{$basePath}/{$moduleName}/Rules";
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $namespace = "{$baseNamespace}\\{$moduleName}\\Rules";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        $fileName = Str::endsWith($name, 'Rule') ? $name : $name; // Wait, rules often end in Rule but sometimes not. Let's make sure it's strict to avoid conflicts.
        if (!Str::endsWith($name, 'Rule')) {
            $fileName = $name . 'Rule';
        }
        $filePath = "{$modulePath}/{$fileName}.php";

        if (!$this->files->exists($filePath)) {
            $content = $this->getStubContent('rule', [
                'namespace' => $namespace,
                'class' => $fileName,
            ]);

            $this->files->put($filePath, $content);
            $this->info("Validation Rule file {$filePath} created successfully.");
        } else {
            $this->info("Validation Rule file {$filePath} already exists.");
        }
    }
}

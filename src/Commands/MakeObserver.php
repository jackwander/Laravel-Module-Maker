<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeObserver extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

    protected $signature = 'jw:make-observer {name} {--module=}';
    protected $description = 'Create a new Observer file for a specific module';

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
        $modulePath = "{$basePath}/{$moduleName}/Observers";
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $namespace = "{$baseNamespace}\\{$moduleName}\\Observers";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        $fileName = Str::endsWith($name, 'Observer') ? $name : $name . 'Observer';
        $filePath = "{$modulePath}/{$fileName}.php";

        if (!$this->files->exists($filePath)) {
            $content = $this->getStubContent('observer', [
                'namespace' => $namespace,
                'class' => $fileName,
            ]);

            $this->files->put($filePath, $content);
            $this->info("Observer file {$filePath} created successfully.");
        } else {
            $this->info("Observer file {$filePath} already exists.");
        }
    }
}

<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeEvent extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

    protected $signature = 'jw:make-event {name} {--module=}';
    protected $description = 'Create a new Event file for a specific module';

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
        $modulePath = "{$basePath}/{$moduleName}/Events";
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $namespace = "{$baseNamespace}\\{$moduleName}\\Events";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        $fileName = Str::endsWith($name, 'Event') ? $name : $name . 'Event';
        $filePath = "{$modulePath}/{$fileName}.php";

        if (!$this->files->exists($filePath)) {
            $content = $this->getStubContent('event', [
                'namespace' => $namespace,
                'class' => $fileName,
            ]);

            $this->files->put($filePath, $content);
            $this->info("Event file {$filePath} created successfully.");
        } else {
            $this->info("Event file {$filePath} already exists.");
        }
    }
}

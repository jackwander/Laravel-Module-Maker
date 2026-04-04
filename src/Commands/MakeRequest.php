<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeRequest extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

    protected $signature = 'jw:make-request {name} {--module=}';
    protected $description = 'Create a new FormRequest file for a specific module';

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
        $modulePath = "{$basePath}/{$moduleName}/Requests";
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');
        $namespace = "{$baseNamespace}\\{$moduleName}\\Requests";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        // Auto append suffix if missing
        $fileName = Str::endsWith($name, 'Request') ? $name : $name . 'Request';
        $filePath = "{$modulePath}/{$fileName}.php";

        if (!$this->files->exists($filePath)) {
            $content = $this->getStubContent('request', [
                'namespace' => $namespace,
                'class' => $fileName,
            ]);

            $this->files->put($filePath, $content);
            $this->info("Request file {$filePath} created successfully.");
        } else {
            $this->info("Request file {$filePath} already exists.");
        }
    }
}

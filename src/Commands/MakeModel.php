<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeModel extends Command
{
    protected $signature = 'jw:make-model {name} {--module=}
                    {--s|service : Create a new service for the module}
                    {--m|migration : Create a new migration for the module}
                    {--c|controller : Create a new controller for the module}
                    {--a|all : Generate a migration, service, and controller}
                    ';
    protected $description = 'Create a new model file for a specific module';

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

        $this->createModelFile($moduleName, $modelName);

        // Check for flags
        $all = $this->option('all');

        if ($all || $this->option('controller')) {
            Artisan::call('jw:make-controller', [
                'name' => $modelName,
                '--module' => $moduleName,
            ]);
        }

        if ($all || $this->option('service')) {
            Artisan::call('jw:make-service', [
                'name' => $modelName,
                '--module' => $moduleName,
            ]);
        }

        if ($all || $this->option('migration')) {
          $this->createMigrationFile($moduleName, $modelName);
        }

        $this->info("Model {$modelName} created successfully.");
    }


    protected function createModelFile($moduleName, $modelName)
    {
        $modulePath = "app/Modules/{$moduleName}/Models";

        // 1. Get the Base Class from Config (with Fallback)
        $baseModelFullClass = config(
            'module-maker.base_classes.model',
            'Jackwander\ModuleMaker\Base\BaseModel'
        );
        $baseModelShortName = class_basename($baseModelFullClass);

        // Ensure the specific module directory exists
        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
            $this->info("Directory {$modulePath} created successfully.");
        }

        $modelName = Str::singular($modelName);
        $modelPath = "{$modulePath}/{$modelName}.php";

        // Calculate Table Name (snake_case and plural)
        $tableName = strtolower(Str::plural(Str::snake($this->argument('name'))));

        if (!$this->files->exists($modelPath)) {
            // clean heredoc syntax
            $modelContent = <<<EOT
    <?php
    
    namespace App\Modules\\{$moduleName}\Models;
    
    use {$baseModelFullClass};
    use Illuminate\Database\Eloquent\Concerns\HasUuids;
    use Illuminate\Database\Eloquent\SoftDeletes;
    
    class {$modelName} extends {$baseModelShortName}
    {
        use SoftDeletes, HasUuids;
    
        protected \$table = '{$tableName}';
    
        protected \$fillable = [
            //
        ];
    
        protected \$keyType = 'string';
    
        public \$incrementing = false;
    }
    EOT;

            $this->files->put($modelPath, $modelContent);
            $this->info("Model file {$modelPath} created successfully.");
        } else {
            $this->info("Model file {$modelPath} already exists.");
        }
    }

  protected function createMigrationFile($moduleName, $modelName)
  {
    $migrationName = 'create_' . strtolower(Str::plural(Str::snake($this->argument('name')))) . '_table';
    // Run the make:migration command
    Artisan::call('jw:make-migration', [
        'name' => $migrationName,
        '--module' => $moduleName,
        '--create' => Str::plural(strtolower(Str::snake($modelName)))
    ]);
  }
}

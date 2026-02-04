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

        $this->info("Model {$modelName} created successfully.");
    }


  protected function createModelFile($moduleName, $modelName)
  {
    $directoryPath = "app/Modules/{$moduleName}";
    $modulePath = "app/Modules/{$moduleName}/Models";
    // Ensure the specific module directory exists
    if (!$this->files->exists($modulePath)) {
        $this->files->makeDirectory($modulePath, 0755, true);
        $this->info("Directory {$modulePath} created successfully.");
    }

    $modelName = Str::singular($modelName); // Remove the trailing 's' from the module name for singular model name
    $modelPath = "{$modulePath}/{$modelName}.php";

    $migrationName = 'create_' . strtolower(Str::plural(Str::snake($this->argument('name')))) . '_table';
    $table_name = '$table = ' . '"'. strtolower(Str::plural(Str::snake($this->argument('name')))) . '"';

    // Run the make:migration command
    Artisan::call('jw:make-migration', [
        'name' => $migrationName,
        '--module' => $moduleName,
        '--create' => Str::plural(strtolower(Str::snake($modelName)))
    ]);

    if (!$this->files->exists($modelPath)) {
      $modelContent = "<?php\n\nnamespace Modules\\{$moduleName}\Models;\n\nuse Jackwander\ModuleMaker\Resources\BaseModel;\nuse Illuminate\Database\Eloquent\Concerns\HasUuids;\nuse Illuminate\Database\Eloquent\SoftDeletes;\n\nclass {$modelName} extends BaseModel\n{\n  use SoftDeletes, HasUuids;\n\n  protected {$table_name};\n\n  protected \$fillable = [\n  ];\n\n  protected \$keyType = 'string';\n\n  public \$incrementing = false;\n}\n\n";
      $this->files->put($modelPath, $modelContent);
      $this->info("Model file {$modelPath} created successfully.");
    } else {
      $this->info("Model file {$modelPath} already exists.");
    }
  }
}

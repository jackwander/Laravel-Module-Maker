<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeFactory extends Command
{
    protected $signature = 'jw:make-factory {name} {--module=}';
    protected $description = 'Create a new model factory for a specific module';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $moduleName = $this->option('module');
        $name = $this->argument('name');

        if (!$moduleName) {
            $this->error('The --module flag is required.');
            return 1;
        }

        // Standardize: Model name is singular, Factory is Singular + "Factory"
        $modelName = Str::studly(Str::singular($name));
        $factoryClassName = "{$modelName}Factory";

        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $baseNamespace = config('module-maker.namespaces.root', 'App\\Modules');

        if (!$this->files->exists("{$basePath}/{$moduleName}")) {
            $this->error("$moduleName not found.");
            return 1;
        }

        $modulePath = "{$basePath}/{$moduleName}/Database/Factories";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        $factoryPath = "{$modulePath}/{$factoryClassName}.php";

        if (!$this->files->exists($factoryPath)) {
            $content = <<<EOT
<?php

namespace {$baseNamespace}\\{$moduleName}\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use {$baseNamespace}\\{$moduleName}\Models\\{$modelName};

class {$factoryClassName} extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected \$model = {$modelName}::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'name' => \$this->faker->name(),
        ];
    }
}
EOT;

            $this->files->put($factoryPath, $content);
            $this->info("Factory created: {$factoryPath}");
            $this->line("\n💡 Remember to add the <fg=cyan>newFactory()</> method to your <fg=yellow>{$modelName}</> model!");
        } else {
            $this->warn("Factory file: {$factoryPath} already exists.");
        }
    }
}

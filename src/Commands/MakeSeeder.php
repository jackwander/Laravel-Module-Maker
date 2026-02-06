<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeSeeder extends Command
{
    protected $signature = 'jw:make-seeder {name} {--module=}';
    protected $description = 'Create a new seeder file for a specific module';

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

        // Standardize naming: Singular + "Seeder" suffix
        $className = Str::studly(Str::singular($name));
        if (!Str::endsWith($className, 'Seeder')) {
            $className .= 'Seeder';
        }

        $modulePath = "app/Modules/{$moduleName}/Database/Seeders";

        if (!$this->files->exists($modulePath)) {
            $this->files->makeDirectory($modulePath, 0755, true);
        }

        $seederPath = "{$modulePath}/{$className}.php";

        if (!$this->files->exists($seederPath)) {
            // Note: Heredoc should not have leading whitespace in the output
            $content = <<<EOT
<?php

namespace App\Modules\\{$moduleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class {$className} extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
    }
}
EOT;

            $this->files->put($seederPath, $content);
            $this->info("Seeder created: {$seederPath}");
            $this->instructCopy($moduleName, $className);
        } else {
            $this->warn("Seeder file: {$seederPath} already exists.");
        }
    }

    protected function instructCopy($moduleName, $seederName)
    {
        $seederNamespace = "App\\Modules\\{$moduleName}\\Database\\Seeders\\{$seederName}";

        $this->line("\n🌱 Add this to your <fg=cyan>database/seeders/DatabaseSeeder.php</>:");
        $this->info("        \$this->call(\\{$seederNamespace}::class);");
    }
}

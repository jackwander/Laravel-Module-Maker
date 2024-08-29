<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeMigration extends Command
{
    protected $signature = 'jw:make-migration {name} {--module=} {--create=} {--table=}';
    protected $description = 'Create a new migration file for a specific module';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $migrationName = $this->argument('name');
        $moduleName = $this->option('module');
        $create = $this->option('create');
        $table = $this->option('table');

        $modulePath = app_path("Modules/{$moduleName}/Database/Migrations");
        if (!$this->files->exists($modulePath)) {
            $this->error("$moduleName not found.");
            return 1;
        }

        if (!$moduleName) {
            $this->error('The --module flag is required.');
            return 1;
        }

        $migrationFileName = date('Y_m_d_His') . "_{$migrationName}.php";
        $migrationFilePath = "{$modulePath}/{$migrationFileName}";

        if ($create) {
            $tableName = Str::snake($create);
            $migrationContent = $this->getCreateMigrationContent($migrationName, $tableName);
        } elseif ($table) {
            $tableName = Str::snake($table);
            $migrationContent = $this->getTableMigrationContent($migrationName, $tableName);
        } else {
            $this->error('You must specify a table, either --create or --table.');
            return 1;
        }

        $this->files->put($migrationFilePath, $migrationContent);
        $this->info("Migration file {$migrationFilePath} created successfully.");
    }

    protected function getCreateMigrationContent($migrationName, $tableName)
    {
        return "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nclass return new class extends Migration\n{\n    public function up(): void\n    {\n        Schema::create('{$tableName}', function (Blueprint \$table) {\n            \$table->uuid('id')->primary();\n            // Add columns here\n            \$table->timestamps();\n            \$table->softDeletes();\n        });\n    }\n\n    public function down(): void\n    {\n        Schema::dropIfExists('{$tableName}');\n    }\n}\n";
    }

    protected function getTableMigrationContent($migrationName, $tableName)
    {
        return "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nclass return new class extends Migration\n{\n    public function up()\n    {\n        Schema::table('{$tableName}', function (Blueprint \$table) {\n            // Add columns or modify existing columns\n        });\n    }\n\n    public function down()\n    {\n        Schema::table('{$tableName}', function (Blueprint \$table) {\n            // Revert changes made in up()\n        });\n    }\n}\n";
    }
}

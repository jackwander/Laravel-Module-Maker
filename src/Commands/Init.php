<?php

namespace Jackwander\ModuleMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Init extends Command
{
    use \Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

    protected $signature = 'jw:init';
    protected $description = 'Initialize the Core module with local base classes and update configuration';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $this->info("🚀 Initializing Module Maker Core...");

        $basePath = config('module-maker.paths.modules', app_path('Modules'));
        $coreNamespace = trim(config('module-maker.namespaces.root', 'App\\Modules'), '\\') . '\\Core';
        $corePath = "{$basePath}/Core";

        if (!$this->files->exists($corePath)) {
            $this->files->makeDirectory($corePath, 0755, true);
        }

        // 1. Generate Base Classes in {root}\Core
        $this->generateBaseClass('core-model', "{$corePath}/BaseModel.php", $coreNamespace);
        $this->generateBaseClass('core-controller', "{$corePath}/BaseApiController.php", $coreNamespace);
        $this->generateBaseClass('core-service', "{$corePath}/BaseService.php", $coreNamespace);

        // 2. Localize the configuration
        $this->updateConfiguration($coreNamespace);

        $this->newLine();
        $this->info("✅ Initialization complete!");
        $this->line("Future modules generated via <fg=cyan>jw:make-model --all</> will now extend your local Core classes.");
    }

    /**
     * Generate a file from a stub.
     */
    protected function generateBaseClass($stub, $path, $namespace)
    {
        if ($this->files->exists($path)) {
            $this->components->twoColumnDetail(basename($path), '<fg=yellow>Skipped (Exists)</>');
            return;
        }

        $content = $this->getStubContent($stub, ['namespace' => $namespace]);
        $this->files->put($path, $content);
        $this->components->twoColumnDetail(basename($path), '<fg=green>Created</>');
    }

    /**
     * Point base_classes at the Core bridge.
     *
     * No config file yet: write the full template. Existing file: never rewrite
     * it; swap only the vendor base-class references by plain string replacement,
     * so every other byte (custom paths, namespaces, extra keys) survives.
     */
    protected function updateConfiguration($coreNamespace)
    {
        $configPath = config_path('module-maker.php');

        if (!$this->files->exists($configPath)) {
            $this->writeConfiguration($configPath);
            return;
        }

        $content = $this->files->get($configPath);
        $original = $content;
        $pasteLines = [];

        foreach (['service' => 'BaseService', 'api_controller' => 'BaseApiController', 'model' => 'BaseModel'] as $key => $class) {
            // Matches both "\Jackwander\..." and "Jackwander\..."; the leading slash is kept as written.
            $vendor = "Jackwander\\ModuleMaker\\Base\\{$class}::class";
            $core = "{$coreNamespace}\\{$class}::class";

            if (str_contains($content, $vendor)) {
                $content = str_replace($vendor, $core, $content);
                $this->components->twoColumnDetail("base_classes.{$key}", '<fg=green>Localized</>');
            } elseif (str_contains($content, $core)) {
                $this->components->twoColumnDetail("base_classes.{$key}", '<fg=yellow>Skipped (already Core)</>');
            } else {
                $this->components->twoColumnDetail("base_classes.{$key}", '<fg=yellow>Skipped (customised)</>');
                $pasteLines[] = "        '{$key}' => \\{$core},";
            }
        }

        if ($content !== $original) {
            $this->files->put($configPath, $content);
        }

        if ($pasteLines) {
            $this->newLine();
            $this->warn('config/module-maker.php has custom base_classes entries, left untouched. To use the Core bridge, set:');
            foreach ($pasteLines as $line) {
                $this->line($line);
            }
        }
    }

    /**
     * Write the localized configuration to the host application.
     */
    protected function writeConfiguration($configPath)
    {
        $configContent = <<<'PHP'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Paths & Namespaces
    |--------------------------------------------------------------------------
    |
    | Configure the root directory and root namespace for your modules.
    | By default, they will be placed in the `app/Modules` directory.
    |
    */
    'paths' => [
        'modules' => base_path('app/Modules'),
        'api_prefix' => 'api/v1',
    ],

    'namespaces' => [
        'root' => 'App\\Modules',
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Classes (Localized to Core)
    |--------------------------------------------------------------------------
    |
    | These classes were generated by jw:init and are located in 
    | App\Modules\Core. You can customize them to your needs.
    |
    */
    'base_classes' => [
        'service'        => \App\Modules\Core\BaseService::class,
        'api_controller' => \App\Modules\Core\BaseApiController::class,
        'model'          => \App\Modules\Core\BaseModel::class,
    ]
];
PHP;

        $this->files->put($configPath, $configContent);
        $this->components->twoColumnDetail('config/module-maker.php', '<fg=green>Localized</>');
    }
}

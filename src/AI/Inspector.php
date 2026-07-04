<?php

namespace Jackwander\ModuleMaker\AI;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class Inspector
{
    protected const COMPONENT_DIRS = [
        'Controllers', 'Models', 'Services', 'Providers', 'Routes',
        'Requests', 'Resources', 'Policies', 'Rules', 'Events',
        'Listeners', 'Jobs', 'Observers',
        'Database/Migrations', 'Database/Seeders', 'Database/Factories',
    ];

    public function inspect(): ProjectSnapshot
    {
        $modulesPath = config('module-maker.paths.modules', app_path('Modules'));
        $serviceBase = config('module-maker.base_classes.service', \Jackwander\ModuleMaker\Base\BaseService::class);

        return new ProjectSnapshot(
            phpVersion: PHP_VERSION,
            laravelVersion: app()->version(),
            packageVersion: static::packageVersion(),
            modulesPath: $modulesPath,
            rootNamespace: config('module-maker.namespaces.root', 'App\\Modules'),
            apiPrefix: config('module-maker.paths.api_prefix', 'api/v1'),
            baseClasses: config('module-maker.base_classes', []),
            bridgeActive: ! str_starts_with(ltrim($serviceBase, '\\'), 'Jackwander\\'),
            modules: $this->discoverModules($modulesPath),
            tooling: $this->detectTooling(),
            composerScripts: $this->hostComposer()['scripts'] ?? [],
            publishedStubs: File::isDirectory(base_path('stubs/vendor/module-maker')),
        );
    }

    public static function packageVersion(): string
    {
        $composer = json_decode(File::get(dirname(__DIR__, 2) . '/composer.json'), true) ?? [];

        return $composer['version'] ?? 'dev';
    }

    protected function discoverModules(string $modulesPath): array
    {
        if (! File::isDirectory($modulesPath)) {
            return [];
        }

        $ignored = config('module-ai.ignored_modules', []);
        $modules = [];

        foreach (File::directories($modulesPath) as $directory) {
            $name = basename($directory);

            if (in_array($name, $ignored)) {
                continue;
            }

            $modules[$name] = array_values(array_filter(
                self::COMPONENT_DIRS,
                fn ($component) => File::isDirectory("{$directory}/{$component}")
            ));
        }

        return $modules;
    }

    protected function detectTooling(): array
    {
        $composer = $this->hostComposer();
        $deps = array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []);

        return [
            'pint' => isset($deps['laravel/pint']) || File::exists(base_path('pint.json')),
            'phpstan' => isset($deps['phpstan/phpstan']) || isset($deps['larastan/larastan'])
                || File::exists(base_path('phpstan.neon')) || File::exists(base_path('phpstan.neon.dist')),
            'psalm' => isset($deps['vimeo/psalm']) || File::exists(base_path('psalm.xml')),
            'rector' => isset($deps['rector/rector']) || File::exists(base_path('rector.php')),
            'pest' => isset($deps['pestphp/pest']),
            'phpunit' => isset($deps['phpunit/phpunit']) && ! isset($deps['pestphp/pest']),
            'sail' => isset($deps['laravel/sail']),
            'docker' => File::exists(base_path('docker-compose.yml')) || File::exists(base_path('compose.yaml')),
            'ci' => File::isDirectory(base_path('.github/workflows')),
        ];
    }

    protected function hostComposer(): array
    {
        $path = base_path('composer.json');

        if (! File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?? [];
    }
}

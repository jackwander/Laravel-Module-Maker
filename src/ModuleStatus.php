<?php

namespace Jackwander\ModuleMaker;

/**
 * Reads and writes the module status file: a JSON object of
 * {"ModuleName": true|false}. Pure filesystem — runs during boot, so it must
 * never touch the cache or database.
 *
 * A missing file, invalid JSON, or a module absent from the file all mean
 * ENABLED. Only an explicit `false` disables a module, and `Core` is always on.
 */
class ModuleStatus
{
    public const ALWAYS_ENABLED = 'Core';

    public static function path(): string
    {
        // Explicit default: mergeConfigFrom() merges top-level keys only, so hosts
        // with a published config never receive this nested key.
        return config('module-maker.paths.statuses', base_path('modules_statuses.json'));
    }

    /**
     * Every module directory name, enabled or not.
     *
     * @return array<int, string>
     */
    public static function modules(): array
    {
        $modulesPath = config('module-maker.paths.modules', app_path('Modules'));

        if (! is_dir($modulesPath)) {
            return [];
        }

        $modules = array_map('basename', glob("{$modulesPath}/*", GLOB_ONLYDIR) ?: []);
        sort($modules);

        return $modules;
    }

    /**
     * Decoded status file: [] when missing, null when unreadable or not a JSON object.
     */
    public static function read(): ?array
    {
        $path = static::path();

        if (! is_file($path)) {
            return [];
        }

        $statuses = json_decode((string) @file_get_contents($path), true);

        return is_array($statuses) ? $statuses : null;
    }

    public static function isEnabled(string $module, ?array $statuses = null): bool
    {
        if ($module === static::ALWAYS_ENABLED) {
            return true;
        }

        $statuses ??= static::read() ?? [];

        return ($statuses[$module] ?? true) !== false;
    }

    /**
     * @param  array<int, string>  $modules
     * @return array<int, string>
     */
    public static function enabled(array $modules): array
    {
        $statuses = static::read() ?? [];

        return array_values(array_filter($modules, fn ($module) => static::isEnabled($module, $statuses)));
    }

    public static function set(string $module, bool $enabled): void
    {
        $statuses = static::read() ?? [];
        $statuses[$module] = $enabled;
        ksort($statuses);

        file_put_contents(static::path(), json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}

<?php

namespace Jackwander\ModuleMaker\Commands\Traits;

use Illuminate\Support\Facades\File;

trait InteractsWithStubs
{
    /**
     * Get the stub content and replace the given variables.
     */
    protected function getStubContent(string $stubName, array $replacements): string
    {
        // Check if published stub exists first
        $publishedPath = base_path("stubs/vendor/module-maker/{$stubName}.stub");
        $packagePath = dirname(__DIR__, 3) . "/stubs/{$stubName}.stub";

        $stubPath = File::exists($publishedPath) ? $publishedPath : $packagePath;

        if (!File::exists($stubPath)) {
            throw new \Exception("Stub not found: {$stubPath}");
        }

        $content = File::get($stubPath);

        foreach ($replacements as $search => $replace) {
            $content = str_replace("{{ {$search} }}", $replace, $content);
        }

        return $content;
    }
}

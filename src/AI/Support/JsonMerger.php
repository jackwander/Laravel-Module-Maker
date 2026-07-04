<?php

namespace Jackwander\ModuleMaker\AI\Support;

use Illuminate\Support\Facades\File;

class JsonMerger
{
    /**
     * Deep-merge data into a JSON file, creating it when missing.
     * Only the provided keys are owned; everything else is preserved.
     *
     * @return string created|updated|unchanged
     */
    public function merge(string $path, array $data): string
    {
        $exists = File::exists($path);
        $existing = $exists ? (json_decode(File::get($path), true) ?? []) : [];

        $merged = array_replace_recursive($existing, $data);

        if ($exists && $merged === $existing) {
            return 'unchanged';
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return $exists ? 'updated' : 'created';
    }
}

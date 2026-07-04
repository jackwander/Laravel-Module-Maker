<?php

namespace Jackwander\ModuleMaker\AI\Support;

use Illuminate\Support\Facades\File;

class ManagedBlock
{
    /**
     * Insert or replace a marker-delimited block inside a shared file.
     * Content outside the markers is never touched.
     *
     * @return string created|updated|unchanged
     */
    public function apply(string $path, string $id, string $content): string
    {
        $begin = "<!-- {$id}:begin -->";
        $end = "<!-- {$id}:end -->";
        $block = $begin . "\n" . trim($content) . "\n" . $end;

        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $block . "\n");

            return 'created';
        }

        $existing = File::get($path);
        $pattern = '/' . preg_quote($begin, '/') . '.*?' . preg_quote($end, '/') . '/s';

        if (preg_match($pattern, $existing)) {
            $updated = preg_replace($pattern, $block, $existing, 1);

            if ($updated === $existing) {
                return 'unchanged';
            }

            File::put($path, $updated);

            return 'updated';
        }

        File::put($path, rtrim($existing) . "\n\n" . $block . "\n");

        return 'updated';
    }
}

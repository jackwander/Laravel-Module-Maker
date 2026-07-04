<?php

namespace Jackwander\ModuleMaker\AI\Sections;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Contracts\Section;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;
use Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

class AiRulesSection implements Section
{
    use InteractsWithStubs;

    public function key(): string
    {
        return 'ai-rules';
    }

    public function title(): string
    {
        return 'Rules for AI Assistants';
    }

    public function render(ProjectSnapshot $snapshot, Depth $depth): string
    {
        $toolingRules = [];

        if ($snapshot->toolingEnabled('pint')) {
            $toolingRules[] = '- Run `vendor/bin/pint` on files you create or modify.';
        }
        if ($snapshot->toolingEnabled('phpstan')) {
            $toolingRules[] = '- Code must pass PHPStan (`vendor/bin/phpstan analyse`).';
        }
        if ($snapshot->toolingEnabled('psalm')) {
            $toolingRules[] = '- Code must pass Psalm (`vendor/bin/psalm`).';
        }
        $toolingRules[] = "- Write tests for new behavior and run `{$snapshot->testCommand()}` before finishing.";

        return $this->getStubContent('ai/ai-rules', [
            'modulesPath' => $snapshot->modulesPath,
            'rootNamespace' => $snapshot->rootNamespace,
            'apiPrefix' => $snapshot->apiPrefix,
            'toolingRules' => implode("\n", $toolingRules),
            'customGuidelines' => $this->customGuidelines(),
        ]);
    }

    protected function customGuidelines(): string
    {
        $path = config('module-ai.custom_guidelines_path') ?: base_path('.ai/custom');

        if (! File::isDirectory($path)) {
            return '';
        }

        $blocks = [];

        foreach (File::files($path) as $file) {
            if ($file->getExtension() === 'md') {
                $blocks[] = trim(File::get($file->getPathname()));
            }
        }

        return $blocks
            ? "\n## Organization Guidelines\n\n" . implode("\n\n", $blocks) . "\n"
            : '';
    }
}

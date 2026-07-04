<?php

namespace Jackwander\ModuleMaker\AI\Support;

class ProjectSnapshot
{
    public function __construct(
        public string $phpVersion,
        public string $laravelVersion,
        public string $packageVersion,
        public string $modulesPath,
        public string $rootNamespace,
        public string $apiPrefix,
        public array $baseClasses,
        public bool $bridgeActive,
        public array $modules,
        public array $tooling,
        public array $composerScripts,
        public bool $publishedStubs,
    ) {
    }

    public function toolingEnabled(string $key): bool
    {
        return (bool) ($this->tooling[$key] ?? false);
    }

    public function testCommand(): string
    {
        return $this->toolingEnabled('pest') ? 'vendor/bin/pest' : 'vendor/bin/phpunit';
    }
}

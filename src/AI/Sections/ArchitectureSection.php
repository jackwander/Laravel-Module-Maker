<?php

namespace Jackwander\ModuleMaker\AI\Sections;

use Jackwander\ModuleMaker\AI\Contracts\Section;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;
use Jackwander\ModuleMaker\Commands\Traits\InteractsWithStubs;

class ArchitectureSection implements Section
{
    use InteractsWithStubs;

    public function key(): string
    {
        return 'architecture';
    }

    public function title(): string
    {
        return 'Project Architecture';
    }

    public function render(ProjectSnapshot $snapshot, Depth $depth): string
    {
        $bridgeStatus = $snapshot->bridgeActive
            ? 'ACTIVE — generated classes extend the local bridge classes in `App\\Modules\\Core` (created by `jw:init`). Add project-wide behavior there, never in vendor.'
            : 'NOT INITIALIZED — generated classes extend the vendor `Jackwander\\ModuleMaker\\Base\\*` classes directly. Run `php artisan jw:init` to create a local bridge.';

        if ($depth === Depth::Compressed) {
            return "# {$this->title()}\n\n"
                . "- Modular Laravel app: features live in isolated modules under `{$snapshot->modulesPath}` (`{$snapshot->rootNamespace}\\{Module}` namespace).\n"
                . "- Inheritance chain: Vendor `Base\\*` -> `App\\Modules\\Core\\*` bridge -> module class. Bridge: {$bridgeStatus}\n"
                . "- Module `{Module}ServiceProvider` classes and `Routes/api.php` files are auto-discovered and registered; routes are prefixed `{$snapshot->apiPrefix}`.\n"
                . "- Never edit files in `vendor/`; customize via the Core bridge or published stubs.\n";
        }

        return $this->getStubContent('ai/architecture', [
            'modulesPath' => $snapshot->modulesPath,
            'rootNamespace' => $snapshot->rootNamespace,
            'apiPrefix' => $snapshot->apiPrefix,
            'serviceBase' => $snapshot->baseClasses['service'] ?? 'Jackwander\\ModuleMaker\\Base\\BaseService',
            'controllerBase' => $snapshot->baseClasses['api_controller'] ?? 'Jackwander\\ModuleMaker\\Base\\BaseApiController',
            'modelBase' => $snapshot->baseClasses['model'] ?? 'Jackwander\\ModuleMaker\\Base\\BaseModel',
            'bridgeStatus' => $bridgeStatus,
            'packageVersion' => $snapshot->packageVersion,
            'laravelVersion' => $snapshot->laravelVersion,
        ]);
    }
}

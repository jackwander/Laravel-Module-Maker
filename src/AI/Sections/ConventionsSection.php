<?php

namespace Jackwander\ModuleMaker\AI\Sections;

use Jackwander\ModuleMaker\AI\Contracts\Section;
use Jackwander\ModuleMaker\AI\Support\Depth;
use Jackwander\ModuleMaker\AI\Support\ProjectSnapshot;

class ConventionsSection implements Section
{
    public function key(): string
    {
        return 'conventions';
    }

    public function title(): string
    {
        return 'Naming & Namespace Conventions';
    }

    public function render(ProjectSnapshot $snapshot, Depth $depth): string
    {
        $ns = $snapshot->rootNamespace;

        $table = <<<MD
        | Artifact | Convention | Example (input: `CivilStatus`, module: `Person`) |
        | --- | --- | --- |
        | Module directory | StudlyCase | `{$snapshot->modulesPath}/Person` |
        | Model class | singular StudlyCase | `{$ns}\\Person\\Models\\CivilStatus` |
        | Table name | plural snake_case | `civil_statuses` |
        | Controller | pluralized + `Controller` | `{$ns}\\Person\\Controllers\\CivilStatusesController` |
        | Service | singular + `Service` | `{$ns}\\Person\\Services\\CivilStatusService` |
        | Seeder | singular + `Seeder` | `Database/Seeders/CivilStatusSeeder.php` |
        | Factory | singular + `Factory` | `Database/Factories/CivilStatusFactory.php` |
        | Policy | singular + `Policy` | `Policies/CivilStatusPolicy.php` |
        | Observer | singular + `Observer` | `Observers/CivilStatusObserver.php` |
        | API Resource | singular + `Resource` | `Resources/CivilStatusResource.php` |
        | Form Request | as given | `Requests/StoreCivilStatusRequest.php` |
        | Migration | snake_case description | `create_civil_statuses_table` |
        | Route prefix | `{$snapshot->apiPrefix}/{module-kebab}` | `{$snapshot->apiPrefix}/civil-status` |
        MD;

        $rules = <<<MD
        - Every class namespace follows `{$ns}\\{Module}\\{ComponentDir}` exactly; the directory path mirrors the namespace (PSR-4).
        - Models use UUID primary keys (`HasUuids`, `\$keyType = 'string'`, `\$incrementing = false`) and SoftDeletes by default.
        - `\$fillable` must be maintained on every model — services filter update input against it and use it for search columns (never schema introspection).
        - Services receive their model via constructor injection and act as repositories (CRUD + filtering inherited from BaseService).
        - Controllers stay thin: constructor wires `service`, human-readable module label, and optional Resource class into the parent; CRUD comes from BaseApiController.
        MD;

        if ($depth === Depth::Compressed) {
            return "# {$this->title()}\n\n{$table}\n";
        }

        return "# {$this->title()}\n\n{$table}\n\n## Rules\n\n{$rules}\n";
    }
}

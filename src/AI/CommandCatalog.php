<?php

namespace Jackwander\ModuleMaker\AI;

use Illuminate\Support\Facades\Artisan;

class CommandCatalog
{
    protected const HIDDEN_OPTIONS = [
        'help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env', 'silent',
    ];

    /** What each generator creates, relative to the module (or project) root. */
    protected const OUTPUTS = [
        'jw:init' => ['app/Modules/Core/{BaseModel,BaseApiController,BaseService}.php', 'config/module-maker.php (localized)'],
        'jw:make-module' => ['Providers/{Name}ServiceProvider.php', 'Routes/api.php', 'Controllers/{Names}Controller.php', 'Models/{Name}.php', 'Services/{Name}Service.php'],
        'jw:make-model' => ['Models/{Name}.php (+ Migration/Service/Controller/Resource via flags)'],
        'jw:make-controller' => ['Controllers/{Names}Controller.php', 'Routes/api.php entry'],
        'jw:make-service' => ['Services/{Name}Service.php'],
        'jw:make-migration' => ['Database/Migrations/{timestamp}_{name}.php'],
        'jw:make-seeder' => ['Database/Seeders/{Name}Seeder.php'],
        'jw:make-factory' => ['Database/Factories/{Name}Factory.php'],
        'jw:make-resource' => ['Resources/{Name}Resource.php'],
        'jw:make-request' => ['Requests/{Name}Request.php'],
        'jw:make-job' => ['Jobs/{Name}.php'],
        'jw:make-event' => ['Events/{Name}.php'],
        'jw:make-listener' => ['Listeners/{Name}.php'],
        'jw:make-policy' => ['Policies/{Name}Policy.php'],
        'jw:make-rule' => ['Rules/{Name}.php'],
        'jw:make-observer' => ['Observers/{Name}Observer.php'],
        'jw:check' => [],
        'jw:ai:init' => ['.ai/*.md', 'platform entry files', '.mcp.json'],
        'jw:mcp' => [],
    ];

    /** @return array<string, array{name:string,description:string,arguments:array,options:array,creates:array}> */
    public function all(): array
    {
        $catalog = [];

        foreach (Artisan::all() as $name => $command) {
            if (! str_starts_with($name, 'jw:')) {
                continue;
            }

            $definition = $command->getDefinition();

            $arguments = [];
            foreach ($definition->getArguments() as $argument) {
                $arguments[] = [
                    'name' => $argument->getName(),
                    'required' => $argument->isRequired(),
                    'description' => $argument->getDescription(),
                ];
            }

            $options = [];
            foreach ($definition->getOptions() as $option) {
                if (in_array($option->getName(), self::HIDDEN_OPTIONS)) {
                    continue;
                }

                $options[] = [
                    'name' => $option->getName(),
                    'shortcut' => $option->getShortcut(),
                    'accepts_value' => $option->acceptValue(),
                    'description' => $option->getDescription(),
                ];
            }

            $catalog[$name] = [
                'name' => $name,
                'description' => $command->getDescription(),
                'arguments' => $arguments,
                'options' => $options,
                'creates' => self::OUTPUTS[$name] ?? [],
            ];
        }

        ksort($catalog);

        return $catalog;
    }

    public function find(string $name): ?array
    {
        return $this->all()[$name] ?? null;
    }

    public function usage(array $entry): string
    {
        $parts = [$entry['name']];

        foreach ($entry['arguments'] as $argument) {
            $parts[] = $argument['required'] ? '{' . $argument['name'] . '}' : '[{' . $argument['name'] . '}]';
        }

        foreach ($entry['options'] as $option) {
            $flag = '--' . $option['name'] . ($option['accepts_value'] ? '=' : '');
            $parts[] = '[' . $flag . ']';
        }

        return implode(' ', $parts);
    }

    public function markdownTable(): string
    {
        $rows = ["| Command | Description |", "| --- | --- |"];

        foreach ($this->all() as $entry) {
            $rows[] = '| `' . $this->usage($entry) . '` | ' . $entry['description'] . ' |';
        }

        return implode("\n", $rows);
    }
}

# Contributing

## Setup and tests

```sh
composer install
vendor/bin/pest
vendor/bin/pest --filter=X
```

Tests run via Orchestra Testbench. There is no host application in this repository; never run `php artisan` here.

## Conventions

- Stubs use `{{ placeholder }}` syntax; spaces inside the braces are required.
- Generators resolve parent classes from `config('module-maker.base_classes.*')`; never hardcode them.
- Model names are singular; table names are plural snake_case; seeder and factory names use a singular name plus the `Seeder` or `Factory` suffix.
- Do not add strict type hints to `Base\*` public method signatures: host applications override them loosely.
- Never add to or edit `src/Resources`; these are deprecated backward compatibility shims.
- `BaseService::update()` must keep filtering input by `getFillable()`.
- Do not access the database or cache during boot or module discovery.
- Generator tests must call `File::deleteDirectory(app_path('Modules'))` in `tearDown`.

## Adding a `jw:make-*` command

1. Add `stubs/{thing}.stub` using the stub syntax above.
2. Add `src/Commands/Make{Thing}.php` using `InteractsWithStubs`, with signature `jw:make-{thing} {name} {--module=}`. Validate that the module exists and skip generation if the file already exists.
3. Register the command in `ModuleServiceProvider::registerCommands()`.
4. Optionally wire a flag and an `Artisan::call` invocation in `MakeModel`.
5. Add a Pest feature test in `tests/Feature`, a README section, and entries in CHANGELOG and RELEASES.

## Commits and pull requests

Use `fix: <summary> vX.Y.Z` or `feat: <summary> vX.Y.Z` for commit messages. Pull requests target `master`.

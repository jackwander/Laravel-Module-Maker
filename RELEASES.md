# 🐛 Release v2.7.2 — Boot Crash Fix (2026-07-07)

A critical fix for a boot-time crash. In some projects — particularly when another package's provider loaded first — installing Module Maker could take the whole app down with `Target class [cache] does not exist` before anything rendered. If you hit that, this release resolves it.

### 🐛 Bug Fixes

- **`Target class [cache] does not exist` on boot**: The provider discovered and registered your module providers inside `register()`, and that discovery cached the module list via `cache()`. But `register()` runs while providers are still being registered — the cache service may not be bound yet — so resolving it threw and aborted boot. Discovery now runs in the container's **booting** phase instead, where the cache service is guaranteed to be available. Module providers are still registered before the boot loop reaches them, so their own `boot()` (migrations and the like) fires exactly as before.

  ```php
  // register() no longer touches the cache — it just queues the work:
  $this->app->booting(function () {
      $this->registerModuleProviders();
  });
  ```

### ⚡ Performance & Logic

- **One discovery path**: Provider registration and route booting now share a single cache-aware `discoverModules()` helper. The `module-maker.modules` cache (kept forever outside `local`/`testing`) now covers route discovery too, which previously re-scanned the filesystem on every request. Added regression tests that reproduce the exact boot-time cache window.

---

# 🐛 Release v2.7.1 — Interactive Picker Fix (2026-07-05)

A quick follow-up to v2.7.0. If you ran `jw:ai:init` without the `--platforms` flag and picked platforms from the interactive prompt, the command blew up with `Undefined array key "Claude Code (CLAUDE.md + .mcp.json)"` before writing a thing. This release makes the picker work as intended.

### 🐛 Bug Fixes

- **`jw:ai:init` interactive selection**: The platform prompt built its choices as a plain list keyed by numeric index, then handed Symfony the human-readable labels as the multiselect default. Symfony resolves multiselect defaults *by choice key*, so it tried to look those labels up as keys and failed. The picker now uses an associative `name => label` map — Symfony shows the friendly label, resolves the default by key, and hands back the platform name directly. As a bonus, you can now type `claude` / `cursor` at the prompt instead of the full label.

  ```bash
  php artisan jw:ai:init
  # → Which AI platforms should be configured? [claude]:
  #   [claude ] Claude Code (CLAUDE.md + .mcp.json)
  #   [cursor ] Cursor (.cursor/rules + .cursor/mcp.json)
  #   ...
  ```

  Passing `--platforms=claude,cursor` was never affected. Added a regression test covering the interactive path, which previously had no coverage.

---

# 🚀 Release v2.7.0 — AI Context Generator & MCP Server (2026-07-04)

This release makes Laravel Module Maker AI-native. One command teaches any modern AI coding assistant your modular architecture — and gives it live tools to scaffold correctly, every time.

### 🚀 New Features

#### 🤖 `jw:ai:init` — AI Context Generator
Run a single command and get a complete, structured AI context for your project:

```bash
php artisan jw:ai:init
```

- Generates canonical guidelines in `.ai/` — architecture, naming conventions, the full generator catalog, AI rules, module inventory, feature workflow, detected tooling, and reusable prompt templates.
- Writes platform entry files for **Claude Code** (`CLAUDE.md` + `.mcp.json`), **Cursor** (`.cursor/rules` + `.cursor/mcp.json`), **GitHub Copilot** (`.github/copilot-instructions.md`), and the **AGENTS.md** standard (Codex, Gemini CLI, and friends).
- Auto-detects your environment: Laravel/PHP versions, Pint, PHPStan, Psalm, Rector, Pest/PHPUnit, Sail, Docker, CI — and adapts the generated rules accordingly.
- Choose your context size with `--depth=full|compressed|summary`, preview with `--dry-run`, and keep everything current with the CI-safe `--refresh`.
- Shared files are updated through marker-delimited **managed blocks** — your own content is never touched, and re-runs are fully idempotent.

#### 🔌 `jw:mcp` — Zero-Dependency MCP Server
A built-in Model Context Protocol server (stdio JSON-RPC, no new composer dependencies) that AI assistants query live:

- `list_modules`, `module_structure`, `application_info` — real project state, never stale.
- `list_generators`, `generator_info` — the command catalog reflected straight from the registered `jw:*` signatures.
- `get_guidelines` — any guideline topic rendered on demand at the depth the model needs.
- `run_generator` — the headline: assistants execute your actual generators (with dry-run preview) instead of hand-writing boilerplate, so conventions are enforced by construction. Disable anytime via `module-ai.mcp.allow_run_generator`.

#### ⚙️ `config/module-ai.php`
Full control over platforms, depth, section toggles, ignored modules, organization coding standards (drop markdown into `.ai/custom/`), and custom platform adapters — new AI tools plug in without touching package internals.

---

# 🚀 Release v2.6.2 — Update Logic Sanitization (2026-04-26)

This patch improves the robustness of the `update` method in `BaseService` by adding explicit data sanitization.

### 🛠️ Improvements
- **Explicit Data Sanitization**: The `BaseService::update()` method now manually filters the input `$data` against the model's `fillable` columns before persistence. This provides an additional layer of security and ensures only valid fields are processed during updates.
- **Refined Update Fetching**: Optimized the internal model resolution within the update workflow to ensure smoother execution across varying model configurations.

---

# 🚀 Release v2.6.1 — Hybrid Compatibility Patch (2026-04-10)

This patch release addresses a strict typing regression introduced in v2.6.0 that caused fatal signature mismatch errors in existing modules.

### 🐛 Bug Fixes
- **Loosened Method Signatures**: Reverted strict argument type hints and return type declarations across all core `Base` classes (`Service`, `Controller`, `Model`). This ensures that legacy modules can safely extend the core package without needing to update their method signatures to match the new PSR-strict standards.

### ⚡ Performance & Logic
- **Optimizations Maintained**: All internal performance improvements, including the **Zero-DB Schema Discovery** and optimized repository queries, remain active and functional under the loosened signatures.

---

# 🚀 Release v2.6.0 — Core Initialization & Architectural Optimization (2026-04-09)

This minor feature update introduces the `jw:init` command to streamline project initialization and includes significant performance optimizations for the foundational base classes.

### 🚀 New Features
- **`jw:init` Command**: Securely bootstrap your `Core` module in `App\Modules\Core`. This command generates localized base classes that inherit from the package defaults, giving you a project-wide bridge for custom logic.
- **Automated Configuration**: `jw:init` now automatically localizes your `config/module-maker.php` to point to your new project-specific base classes.

### ⚡ Performance & Logic Improvements
- **Zero-DB Schema Discovery**: `BaseService` now resolves model metadata (fillable columns) without hitting the database, drastically improving efficiency on large data sets.
- **Strict Type Hinting**: Comprehensive PSR-compliant type hints and return types added across all core `Base` classes (`Service`, `Controller`, `Model`).
- **Standardized API Responses**: `BaseApiController` now returns proper `201 Created` status codes for resource storage.
- **Improved Inheritance Pattern**: Refactored the internal engine to promote the "Extend-and-Override" pattern for better maintainability.

---

# 🚀 Release v2.5.3 — Laravel 13 Support

This version officially introduces support for Laravel 13, ensuring your modular architecture stays compatible with the latest ecosystem updates.

### ✨ What’s New in v2.5.3

#### 🏗️ Laravel 13 Compatibility
I have expanded the package constraints to include Laravel 13 (`^13.0`) and updated `orchestra/testbench` to ensure full compatibility. This resolves dependency conflicts where `laravel/framework` replaces `illuminate` components.

---

# 🚀 Release v2.5.2 — Config Publishing Polish

This patch improves the developer experience by standardizing the `vendor:publish` workflow.

### ✨ What’s New in v2.5.2

#### 🛠️ Standardized Tagging
I have updated the `ModuleServiceProvider` to support the generic `config` tag during `vendor:publish`. This aligns the package with standard Laravel conventions and resolves issues where common publishing commands were failing.
```bash
php artisan vendor:publish --tag="config"
```

---

# 🚀 Release v2.5.1 — Centralized Route Discovery (Regression Fix)

This patch releases a critical fix for route discovery across all modules.

### ✨ What’s New in v2.5.1

#### 🛰️ Centralized Route Registration
We have moved the responsibility of loading module routes from individual modules into the core package engine. This successfully resolves a regression where "older" modules (generated via previous stub versions) were failing to have their routes registered in `route:list`.

#### 🛠️ Robust discovery logic
Enhanced the module discovery engine with stricter directory validation, ensuring a smoother "zero-config" experience even in complex environments.

---

# 🚀 Release v2.5.0 — Domain-Driven Architecture Commands

This version introduces a massive expansion of the generation ecosystem, allowing for completely isolated Domain-Driven Design architectures inside your modules.

### ✨ What’s New in v2.5.0

#### 🛠️ Extended Artisan Engine
I introduced 7 new generation commands to fully support enterprise-scale architecture:
* **`jw:make-request`**: Generates form validation Requests.
* **`jw:make-job`**: Generates queued background Jobs.
* **`jw:make-event` & `jw:make-listener`**: Scaffolds modular Events architecture.
* **`jw:make-policy` & `jw:make-rule`**: Creates Authorization Policies and Custom Validation Rules.
* **`jw:make-observer`**: Creates Observers for hooking into Eloquent lifecycles.

#### 💎 API Resource Polish
Fully polished API Resource boilerplate generation including seamless Controller dependency injection during `--all` commands! 

---

# 🚀 Release v2.4.0 — Performance & Publishable Stubs

This version focuses strictly on optimizing package performance, enhancing developer flexibility, and solidifying the testing framework.

### ✨ What’s New in v2.4.0

#### 🛠️ Publishable Stubs
By replacing massive internal Heredoc strings with flexible template files, developers can now publish and fully customize their generation logic:
```bash
php artisan vendor:publish --tag="module-maker-stubs"
```

#### ⚡ Performance Boost
Upgraded module file discovery to leverage Laravel's caching mechanisms, completely eliminating dynamic file-system scans in production workflows and drastically streamlining boot times!

#### 🧪 Pest PHP / Testbench
Added foundational support and basic feature tests using Orchestra Testbench and Pest PHP for deep package validation.

### ⚠️ Architecture Refactor
* **Base Namespace:** Relocated the base blueprint classes from `Jackwander\ModuleMaker\Resources` to `Jackwander\ModuleMaker\Base` to better align with conventional Laravel naming.
* Legacy shims have been preserved to avoid breaking existing projects, but usage is soft-deprecated.

---

# 🚀 Release v2.3.0 — Modular Factories

### ✨ What’s New in v2.3.0

* **`jw:make-factory` Command:** Generate modular Eloquent factories directly within the `Database/Factories` directory.
* **Smart Context:** Controller and Model generators now elegantly interpret multi-word inputs (e.g. `PersonSalaryComponent`) into accurate UI labels.
* **Namespacing:** Factory generation strictly links dependencies automatically by explicitly declaring the module-specific `$model` property.

---

# 🚀 Release v2.2.0 — Modular Seeders

### ✨ What’s New in v2.2.0
* **`jw:make-seeder` Command:** Added the ability to construct modular database seeders with strictly enforced naming conventions.
* **Smart Output Engine:** Terminal feedback was updated to instantly output copy-paste snippets for registering Seeders directly into your `DatabaseSeeder.php`!
* **Heredoc Alignment:** Refactored multiple hereditary templates to strip unwanted indentation formatting anomalies.

---

# 🚀 Release v2.1.1 — Laravel 11/12 Dependency Fix (2026-02-05)

This patch resolves a framework dependency conflict.

### 🐛 Bug Fixes
- **Dependency Constraints**: Updated package dependencies to correctly support both Laravel 11 and Laravel 12, fixing a `laravel/framework` conflict that blocked installation on Laravel 12 projects.

---

# 🚀 Release v2.1.0 — Architecture Bridge & Vendor Config

### ✨ What’s New in v2.1.0
* **Configurable Inheritance:** Introduced `config/module-maker.php` to define explicit routing structures for base models, base controllers, and base services! 
* **Vendor Config Publishing:** Supported `vendor:publish` workflows to natively override root configurations.
* **Bridge Architecture:** Generators were rebuilt to seamlessly read the newly published config, giving developers the ability to abstract standard endpoints into "App Core" bridge layers without editing internal logic.

---

# 🚀 Release v2.0.2 — Production Metadata Polish (2026-02-05)

A small follow-up to the v2.0.0 Zero-Config milestone.

### 🛠️ Improvements
- **Package Metadata**: Refined `composer.json` metadata and dependencies for production readiness.
- **Documentation**: Cleaned up the README and release notes for the 2.0.x line.

---

# 🚀 Release v2.0.0 — Zero-Config Milestone
I am excited to announce the release of **v2.0.0**! This version represents a complete structural overhaul I’ve built to make modular Laravel development faster, cleaner, and completely configuration-free.



### 💡 The Big Change: Path B (App\Modules)
The biggest update I've implemented in this version is the move to the `App\Modules` namespace. By nesting modules within the project's native `App\` root, I have achieved:

* **Zero Manual Config:** You no longer need to edit `composer.json`.
* **Standard Compliance:** 100% PSR-4 compatibility (no more terminal warnings).
* **Instant Autoloading:** Your new modules work the second you create them.

---

### ✨ What’s New in v2.0.0

#### 🛠️ Powerful Model Maker Flags
I have updated the `jw:make-model` command to mirror standard Laravel behavior, allowing you to generate an entire modular stack in one line:

* **`-m` | `--migration`**: Creates a migration file inside the module's Database folder.
* **`-s` | `--service`**: Creates a Service class with pre-injected Model dependencies.
* **`-c` | `--controller`**: Creates a Controller and registers API routes.
* **`-a` | `--all`**: The "Super Shortcut"—generates the Migration, Model, Service, and Controller.

#### 🔍 System Health Check
I added a new command: `php artisan jw:check`. Use this to quickly verify if your `app/Modules` directory is correctly mapped and see exactly which modules I have detected and loaded into the system.

#### 🛰️ Automatic Discovery
I built an engine that now automatically scans and registers:

* **Service Providers:** Located at `App\Modules\{Name}\Providers`.
* **API Routes:** Located at `App\Modules\{Name}\Routes/api.php` (automatically prefixed with `api/v1`).

---

### ⚠️ Breaking Changes
* **Namespace Migration:** Any classes you created under the old `Modules\` namespace must be updated to `App\Modules\...`.
* **Provider Registration:** If you were manually registering providers in `AppServiceProvider`, you should remove that code; I’ve designed the package to handle discovery automatically now.

---

### 🐛 Bug Fixes
* I resolved the PSR-4 standard violations that occurred during `composer dump-autoload`.
* I corrected variable casing in stubs to ensure `snake_case` is strictly used for model properties.
* I improved cross-platform path resolution to ensure the package works on both Windows and Linux.

---

### 📦 Installation / Upgrade
```bash
composer require jackwander/laravel-module-maker
php artisan jw:check
```

---

# 📜 Legacy Releases (v1.x)

Compact history of the pre-2.0 line. All v1.x releases used the root `Modules\` namespace (replaced by `App\Modules` in v2.0.0).

- **v1.2** (2026-02-05) — Added `--service` (`-s`), `--controller` (`-c`), and `--all` (`-a`) flags to `jw:make-model`; enforced strict `snake_case` for model variables.
- **v1.1.1 – v1.1.3** (2025-04-16) — Removed the hard Illuminate requirement; added `findForPassport` support.
- **v1.1.0** (2024-11-13) — Stopped applying SoftDeletes by default.
- **v1.0.6 – v1.0.9** (2024-10-09) — Made Controller, Model, and Service commands individually available; fixed missing stub variables; removed a stray artisan command.
- **v1.0.5** (2024-09-13) — Introduced the Controller/Model/Service commands; fixed newline spacing in generated files.
- **v1.0.3 – v1.0.4** (2024-08-31) — Fixed the migration `--table` flag; improved migration spacing and comments.
- **v1.0.2** (2024-08-31) — Fixed Migration/Model/Controller generation dependencies.
- **v1.0.1** (2024-08-30) — Added migrations scoped to a specific module; improved MakeModule model content.
- **v1.0.0** (2024-08-30) — Initial release.

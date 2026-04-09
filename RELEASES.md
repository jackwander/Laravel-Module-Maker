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

# 🚀 Release v2.1.0 — Architecture Bridge & Vendor Config

### ✨ What’s New in v2.1.0
* **Configurable Inheritance:** Introduced `config/module-maker.php` to define explicit routing structures for base models, base controllers, and base services! 
* **Vendor Config Publishing:** Supported `vendor:publish` workflows to natively override root configurations.
* **Bridge Architecture:** Generators were rebuilt to seamlessly read the newly published config, giving developers the ability to abstract standard endpoints into "App Core" bridge layers without editing internal logic.

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

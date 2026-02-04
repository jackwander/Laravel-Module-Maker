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

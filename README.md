# Laravel Custom Module Creator

This package provides a robust, modular architecture for Laravel applications. Designed for consistency and maintainability, it allows you to build features in isolation within the `app/Modules` directory.

> **🚀 Zero-Config:** As of v2.0.0, this package automatically handles PSR-4 autoloading, Service Provider registration, and API route discovery. No manual `composer.json` or `app.php` edits are required.

---

## 📋 Requirements

| Requirement | Supported Versions |
| :--- | :--- |
| **PHP** | `^8.2` |
| **Laravel** | `^11.0` |

---

## 📦 Installation

```shell
composer require jackwander/laravel-module-maker
```

---

## 🏗️ Architecture & Inheritance

To keep your application maintainable and scalable, this package encourages an **Intermediate Base Class** (Bridge) pattern. This allows you to customize global behavior—like custom response formatting or shared business logic—without ever touching the `vendor/` directory.

### The Inheritance Chain
Your generated modules follow this hierarchy:

**`Vendor Base`** ➜ **`App Core`** ➜ **`Module File`**

1.  **Vendor Base:** The raw logic provided by the package inside `ModuleMaker/Resources` (Read-only).
2.  **App Core:** Your custom bridge where you add project-specific logic (Editable).
3.  **Module File:** The specific logic for a feature (e.g., `PersonService`).

---

## ⚙️ Configuration & Customization

By default, the generator extends the package's internal resources. To take full control of your architecture, follow these steps to use your own custom "Core" files.

### 1. Publish the Configuration
Publish the config file to your application's config directory:

```shell
php artisan vendor:publish --provider="Jackwander\ModuleMaker\ModuleServiceProvider" --tag="config"
```

### 2. Create Your Core Layer
We recommend creating a `Core` directory to house your bridge classes at `app/Modules/Core/`. Create a file (e.g., `BaseService.php`) and extend the package's resource:

```php
<?php

namespace App\Modules\Core;

// Import the package's base resource from the vendor folder
use Jackwander\ModuleMaker\Resources\BaseService as VendorBaseService;

class BaseService extends VendorBaseService 
{
    /**
     * Add global methods here.
     * Every module generated in the future will inherit these.
     */
    public function customGlobalLogic()
    {
        // Your custom logic here
    }
}
```
### 3. Register Your Custom Base Classes
Update `config/module-maker.php` to tell the generator to use your local files as the parent classes:

```php
// config/module-maker.php

return [
    'base_classes' => [
        'service'    => \App\Modules\Core\BaseService::class,
        'api_controller' => \App\Modules\Core\BaseApiController::class,
        'model'      => \App\Modules\Core\BaseModel::class,
    ]
];
```

### The Benefit
Now, whenever you run php artisan `jw:make-service`, the generated file will automatically extend `App\Modules\Core\BaseService` instead of the vendor class. This gives you total control over your project's architecture while still automating the boring boilerplate.

--- 

## 🛠 Usage

### 1. Creating a New Module
To generate a complete module structure (Folders, Service Providers, and Routes), run:

```shell
php artisan jw:make-module Person
```

### 2. Creating a Model (The Powerhouse Command)
In v2.0.0, the `jw:make-model` command is the most efficient way to build your feature. I have updated it to support standard Laravel-style flags so you can generate the entire stack at once:

```shell
# Generate Model + Migration + Service + Controller + Routes
php artisan jw:make-model CivilStatus --module=Person -a

# Or use specific flags:
php artisan jw:make-model CivilStatus --module=Person -m -s -c
```
Available Flags:

- `-m `| `--migration` : Generate a Migration file.
- `-s` | `--service` : Generate a Service class.
- `-c` | `--controller` : Generate a Controller.
- `-a` | `--all` : Generate All (Full Stack).

### 3. Individual Component Generation
If you need to add a single component to an existing module, you can use these granular commands:

#### Migration

```shell
php artisan jw:make-migration insert_status_column --module=Person --table=persons
```
#### Controller
```shell
php artisan jw:make-controller CivilStatus --module=Person
```

#### Service
```shell
php artisan jw:make-service CivilStatus --module=Person
```

### ✅ System Verification
I have included a health-check command to ensure your environment is correctly configured and that all modules are being detected by the system:
```shell
php artisan jw:check
```

### 📂 Folder Structure
Generated modules follow this PSR-4 compliant structure automatically:

```text
app/Modules/
└── Person/
    ├── Controllers/         # Module-specific Controllers
    ├── Models/              # Eloquent Models
    ├── Services/            # Business Logic / Service Layer
    ├── Providers/           # Module Service Provider (Auto-registered)
    ├── Database/
    │   └── Migrations/      # Module-specific Migrations
    └── Routes/
        └── api.php          # Module API Routes (Prefix: api/v1/person)
```

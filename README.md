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

    The Inheritance Chain
Your generated modules follow this hierarchy:

**`Vendor Base`** ➜ **`App Core`** ➜ **`Module File`**

1.  **Vendor Base:** The raw logic provided by the package inside `ModuleMaker/Base` (Read-only).
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
use Jackwander\ModuleMaker\Base\BaseService as VendorBaseService;

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

### 4. Customizing Stubs
If you want to fully modify the structure of the generated code (adding default traits, custom imports, or different PHPDoc blocks), you can publish the Stubs:

```shell
php artisan vendor:publish --provider="Jackwander\ModuleMaker\ModuleServiceProvider" --tag="module-maker-stubs"
```
This will copy standard Laravel `.stub` files into `stubs/vendor/module-maker/`. The package will automatically read these templates before falling back to the defaults.

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

---

#### Migration

```shell
php artisan jw:make-migration insert_status_column --module=Person --table=persons
```
---

#### Seeder
To generate a new seeder for a specific module, use the `jw:make-seeder` command. The package automatically singularizes the name and appends the `Seeder` suffix for you.

```shell
php artisan jw:make-seeder Status --module=Person
```
What happens next?

**File Creation:** A new seeder is created at `app/Modules/Person/Database/Seeders/StatusSeeder.php`.

**Smart Output:** The terminal will provide a `ready-to-copy` snippet so you can register it instantly.

Example Output:

Seeder created: `app/Modules/Person/Database/Seeders/StatusSeeder.php`

🌱 Add this to your `database/seeders/DatabaseSeeder.php`:

`$this->call(\App\Modules\Person\Database\Seeders\StatusSeeder::class);`

### Why use modular seeders?
>By keeping seeders inside the module, you ensure that your features are completely portable. If you move the Person module to a different project, your data-seeding logic goes with it.
---

### 🧪 Factory Generation

Modular factories require an explicit `$model` property because they live outside the default Laravel namespace. Use the `jw:make-factory` command to generate one:

```shell
php artisan jw:make-factory Person --module=Person
```

### Connecting the Factory to your Model
 If the convention do not apply automatically to your particular application or factory, you may add the `UseFactory` attribute to the model to manually specify the model's factory:

```php
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Modules\Person\Database\Factories\PersonFactory;

#[UseFactory(PersonFactory::class)]
class Person extends model
{

}
```

Alternatively, you may overwrite the `newFactory` method on your model to return an instance of the model's corresponding factory directly:

```php
use App\Modules\Person\Database\Factories\PersonFactory;

/**
 * Create a new factory instance for the model.
 */
protected static function newFactory()
{
    return PersonFactory::new();
}
```

---

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

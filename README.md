# Laravel Custom Module Creator

This package provides a robust, modular architecture for Laravel applications. Designed for consistency and maintainability, it allows you to build features in isolation within the `app/Modules` directory.

> **🚀 Zero-Config:** As of v2.0.0, this package automatically handles PSR-4 autoloading, Service Provider registration, and API route discovery. No manual `composer.json` or `app.php` edits are required.

---

## Usage

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

### System Verification
I have included a health-check command to ensure your environment is correctly configured and that all modules are being detected by the system:
```shell
php artisan jw:check
```

### Folder Structure
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

## Requirements

| Requirement | Supported Versions |
| :--- | :--- |
| **PHP** | `^8.2` |
| **Laravel** | `^11.0` |

---

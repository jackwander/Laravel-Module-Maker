## 2.0.2 - February 05, 2026 (Thursday)

### Zero-Config Milestone
This major release introduces a "plug-and-play" architecture. By migrating to the `App\Modules` namespace, I have eliminated the requirement for manual `composer.json` edits and achieved 100% PSR-4 compliance out of the box.

### Added
- **Zero-Config Autoloading**: All modules now reside under the `App\Modules` namespace, leveraging Laravel's native autoloader for a seamless installation experience.
- **Enhanced Model Maker**: The `jw:make-model` command now supports the following flags for rapid boilerplate generation:
    - `-s` | `--service` : Generate a Service class.
    - `-m` | `--migration` : Generate a migration file for the model.
    - `-c` | `--controller` : Generate a Controller and Routes.
    - `-a` | `--all` : Generate the full stack (Model, Migration, Service, and Controller).
- **Health Check Command**: Introduced `php artisan jw:check` to verify directory existence, namespace mapping, and module loading status.
- **Dynamic Discovery**: Automatic detection and registration of Service Providers and API routes for any module located in `app/Modules`.

### Changed (Breaking Changes)
- **Namespace Refactor**: All generated classes (Models, Controllers, Services, Providers) now use the `App\Modules\{ModuleName}` prefix instead of the previous `Modules` root.
- **Path Resolution**: Standardized internal path handling using `app_path()` and `basename()` to ensure full compatibility across Windows and Linux environments.

### Fixed
- **PSR-4 Compliance**: Resolved "does not comply with PSR-4" warnings previously seen during `composer dump-autoload` by nesting modules within the project's native `App\` namespace.
- **Naming Conventions**: Fixed an issue where stub variables were not consistently following `snake_case` in generated templates.

---

**2.0.0**: https://github.com/jackwander/Laravel-Module-Maker/compare/v1.2...v2.0.0

---

## 1.2 - February 05, 2026 (Thursday)
### Added
- New flags for `jw:make-model` command: `--service` (`-s`) and `--controller` (`-c`) to automate boilerplate generation.
- Support for `--all` (`-a`) flag to generate Model, Migration, Service, and Controller in one command.

### Fixed
- Resolved an issue where model variables were incorrectly formatted; now strictly using **snake_case** for database compatibility.

## 1.1.1, 1.1.3 - April 16, 2025 (Wednesday)
- Remove Illuminate Requirement.
- Add findForPassport.

## 1.1.0 - November 13, 2024 (Wednesday)
- Don't use default SoftDelete.

## 1.0.6 - 1.0.9 - October 09, 2024 (Wednesday)

### Fixes
- Fix missing variables
- Remove other artisan command.

### Added
- Make available Controller, Model, Service command.

## 1.0.5 - September 13, 2024 (Friday)

### Fixes
- Fix new line spaces.

### Added
- Make Controller, Model, Service command.

## 1.0.3, 1.0.4 - August 31, 2024 (Saturday)

### Fixes
- Fix Migration --table flag.
- Update Migration spacing and comments.

## 1.0.2 - August 31, 2024 (Saturday)

###  Fix
- Fix Migration, Model, Controller dependency.

## 1.0.1 - August 30, 2024 (Friday)

### Added

- Migration for specific Module.

### Updated

- Updated content for Model in MakeModule.

## 1.0.0 - August 30, 2024 (Friday)

### Added

- Initial release

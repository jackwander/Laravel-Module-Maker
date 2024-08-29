# Laravel Custom Module Creator

This Custom Module Creator adheres strictly to SDG coding standards and file structure guidelines. It is designed to ensure consistency and maintainability in SDG projects.

## Usage

### Creating a new Module.
To create a new module, run:

```shell
php artisan jw:make-module Person
```

### Creating a New Migration File for a Specific Module
To create a new migration file for a module, use:
```shell
php artisan jw:make-migration insert_status_column --module=Person --table=persons
```

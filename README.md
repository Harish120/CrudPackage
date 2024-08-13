# Laravel CRUD Package

A Laravel package to automatically generate CRUD operations including models, migrations, controllers, resources, and routes based on a specified model with dynamic columns.

## Installation

To install the package, run:

```bash
composer require harry/crudpackage
```

## Usage

After installation, you can generate CRUD operations using the `crud:generate` Artisan command.

### Basic Command

To generate CRUD operations for a model:

```bash
php artisan crud:generate ModelName --columns=name:string,email:string,age:integer
```

### Parameters

- **`model`**: The name of the model for which CRUD operations will be generated.
- **`--columns=`**: A comma-separated list of columns with their types. Example: `name:string,email:string,age:integer`.
    - Supported types: `string`, `integer`, `boolean`, `text`, `date`.
    - Add `?` after the type to make the column nullable. Example: `email:string?`.

### Example Commands

1. Generate CRUD for a model with dynamic columns:
   ```bash
   php artisan crud:generate SocialUser --columns=name:string,address:string?,phone:string,email:string
   ```

2. Generate CRUD for a model without dynamic columns:
   ```bash
   php artisan crud:generate SocialUser
   ```

### Validation Rules

If dynamic columns are provided, the package automatically generates validation rules for the controller. Nullable columns are handled with the `nullable` rule.

## Generated Files

The package generates the following files for the specified model:

- **Model**: `app/Models/ModelName.php`
- **Migration**: `database/migrations/xxxx_xx_xx_create_model_name_table.php`
- **Controller**: `app/Http/Controllers/ModelNameController.php`
- **Resource**: `app/Http/Resources/ModelNameResource.php`
- **Route**: Adds an API resource route in `routes/api.php`

## Contributing

Feel free to submit a pull request if you would like to contribute to the package.

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

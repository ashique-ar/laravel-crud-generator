# Laravel CRUD Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ashique-ar/laravel-crud-generator.svg?style=flat-square)](https://packagist.org/packages/ashique-ar/laravel-crud-generator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ashique-ar/laravel-crud-generator/run-tests?label=tests)](https://github.com/ashique-ar/laravel-crud-generator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ashique-ar/laravel-crud-generator/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ashique-ar/laravel-crud-generator/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ashique-ar/laravel-crud-generator.svg?style=flat-square)](https://packagist.org/packages/ashique-ar/laravel-crud-generator)

A powerful, configuration-driven CRUD API generator for Laravel applications. Generate complete REST APIs with advanced features like permissions, filtering, sorting, validation, and custom business logic handlers - all through simple configuration.

## ✨ Features

- **🚀 Zero-Code CRUD APIs** - Generate complete REST APIs through configuration
- **🔐 Permission Integration** - Built-in support for Spatie Laravel Permission
- **🎯 Custom Logic Handlers** - Extend with custom business logic easily
- **🔍 Advanced Filtering** - Search across multiple fields with configurable operators
- **📊 Smart Sorting** - Multi-field sorting with default configurations
- **⚡ Bulk Operations** - Perform operations on multiple resources at once
- **🗑️ Soft Deletes Support** - Full soft delete support with restore capabilities
- **✅ Automatic Validation** - Validation rules defined in configuration
- **📚 API Documentation** - Auto-generated OpenAPI/Swagger documentation
- **🛠️ Artisan Commands** - Powerful commands for scaffolding and management
- **🧪 Fully Tested** - Comprehensive test suite included

## 📋 Requirements

- PHP 8.1+
- Laravel 10.x | 11.x | 12.x
- Spatie Laravel Permission (automatically installed)

## 📦 Installation

Install the package via Composer:

```bash
composer require ashique-ar/laravel-crud-generator
```

Run the installation command:

```bash
php artisan crud:install
```

This will:
- Publish the configuration file
- Set up middleware
- Register routes
- Generate initial permissions (optional)

## 🚀 Quick Start

### 1. Configure Your First Resource

Edit `config/crud.php` to define your resources:

```php
<?php

return [
    'resources' => [
        'users' => [
            'model' => App\Models\User::class,
            'fillable' => ['name', 'email', 'phone'],
            'validation' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20'
            ],
            'search' => [
                'enabled' => true,
                'fields' => ['name', 'email']
            ],
            'sort' => [
                'enabled' => true,
                'fields' => ['name', 'email', 'created_at'],
                'default' => ['field' => 'created_at', 'direction' => 'desc']
            ],
            'permissions' => [
                'enabled' => true,
                'middleware' => 'check.crud.permission'
            ]
        ],
        
        // Example with model in subfolder
        'user-profiles' => [
            'model' => App\Models\User\Profile::class,
            'fillable' => ['bio', 'avatar', 'social_links'],
            'validation' => [
                'bio' => 'nullable|string|max:1000',
                'avatar' => 'nullable|image|max:2048'
            ]
        ]
    ]
];
```

### 2. Generate Permissions

```bash
php artisan crud:permissions
```

### 3. Start Using Your API

Your CRUD endpoints are now available:

```bash
# List users with pagination, search, and sorting
GET /api/v1/users?search=john&sort=name&direction=asc&page=1

# Create a new user
POST /api/v1/users
{
    "name": "John Doe",
    "email": "john@example.com"
}

# Get a specific user
GET /api/v1/users/1

# Update a user
PUT /api/v1/users/1
{
    "name": "John Smith",
    "email": "johnsmith@example.com"
}

# Delete a user
DELETE /api/v1/users/1
```

## 📖 Available Endpoints

For each configured resource, the following endpoints are automatically generated:

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/{resource}` | List resources with pagination, search, and sorting |
| `POST` | `/api/v1/{resource}` | Create a new resource |
| `GET` | `/api/v1/{resource}/{id}` | Get a specific resource |
| `PUT/PATCH` | `/api/v1/{resource}/{id}` | Update a resource |
| `DELETE` | `/api/v1/{resource}/{id}` | Delete a resource |
| `POST` | `/api/v1/{resource}/bulk` | Bulk operations (if enabled) |
| `GET` | `/api/v1/{resource}/trashed` | List soft-deleted resources (if enabled) |
| `POST` | `/api/v1/{resource}/{id}/restore` | Restore soft-deleted resource (if enabled) |
| `DELETE` | `/api/v1/{resource}/{id}/force` | Force delete resource (if enabled) |
| `GET` | `/api/v1/{resource}/docs` | API documentation for the resource |

### Query Parameters

#### Pagination
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

#### Search
- `search` - Search term across configured searchable fields
- `search_operator` - Search operator: `like`, `exact`, `starts_with`, `ends_with` (default: `like`)

#### Sorting
- `sort` - Field to sort by
- `direction` - Sort direction: `asc` or `desc` (default: `asc`)

#### Filtering
- `filter[field]` - Filter by specific field value
- `filter[field][operator]` - Filter with specific operator (`=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `in`, `between`)

## 🔧 Advanced Usage

### Model Namespacing

The package supports flexible model namespacing to accommodate different project structures:

```php
// Configuration examples for different model locations
'resources' => [
    // Simple model in App\Models
    'users' => [
        'model' => App\Models\User::class,
        // ... other config
    ],
    
    // Model in subfolder
    'profiles' => [
        'model' => App\Models\User\Profile::class,
        // ... other config
    ],
    
    // Model in admin subfolder
    'admin-users' => [
        'model' => App\Models\Admin\User::class,
        // ... other config
    ],
    
    // Model in completely different namespace
    'products' => [
        'model' => Modules\Catalog\Models\Product::class,
        // ... other config
    ]
]
```

When using artisan commands, you can specify models in several ways:

```bash
# Simple class name (assumes App\Models namespace)
php artisan make:crud-logic UserLogic --model=User

# With subfolder (within App\Models)
php artisan make:crud-logic ProfileLogic --model=User\\Profile

# Full namespace
php artisan make:crud-logic ProductLogic --model=Modules\\Catalog\\Models\\Product
```

### Custom Logic Handlers

Create custom business logic for your resources:

```bash
# For a simple model in App\Models
php artisan make:crud-logic UserLogic --model=User

# For a model in a subfolder
php artisan make:crud-logic ProfileLogic --model=User\\Profile

# For a model with full namespace
php artisan make:crud-logic AdminLogic --model=App\\Models\\Admin\\User
```

This generates:

```php
<?php

namespace App\Services\Crud;

use AshiqueAr\LaravelCrudGenerator\Services\Crud\BaseCrudLogic;
use App\Models\User;

class UserLogic extends BaseCrudLogic
{
    protected string $modelClass = User::class;
    
    public function beforeCreate(array $data, Request $request): array
    {
        // Add custom logic before creating
        $data['created_by'] = auth()->id();
        return $data;
    }
    
    public function afterCreate(Model $user, Request $request): void
    {
        // Send welcome email
        Mail::to($user)->send(new WelcomeEmail($user));
    }
    
    // Override other methods as needed
}
```

Then update your configuration:

```php
'users' => [
    'logic' => App\Services\Crud\UserLogic::class,
    // ... other configuration
]
```

### Adding New Resources

Use the artisan command to quickly add new resources:

```bash
# For a simple model in App\Models
php artisan make:crud-resource posts --model=Post

# For a model in a subfolder
php artisan make:crud-resource user-profiles --model=User\\Profile

# For a model with full namespace
php artisan make:crud-resource admin-users --model=App\\Models\\Admin\\User
```

This will interactively help you configure:
- Model class and table
- Fillable fields
- Validation rules
- Search and sort configuration
- Permissions
- Relations
- Custom logic handler (optional)

### Bulk Operations

Perform operations on multiple resources:

```bash
# Bulk delete
POST /api/v1/users/bulk
{
    "operation": "delete",
    "ids": [1, 2, 3]
}

# Bulk update
POST /api/v1/users/bulk
{
    "operation": "update",
    "ids": [1, 2, 3],
    "data": {
        "status": "active"
    }
}

# Bulk restore (soft deletes)
POST /api/v1/users/bulk
{
    "operation": "restore",
    "ids": [1, 2, 3]
}
```

### Permissions

The package integrates seamlessly with Spatie Laravel Permission:

```bash
# Generate permissions for all resources
php artisan crud:permissions

# Generated permissions (for 'users' resource):
# - view-users
# - create-users  
# - edit-users
# - delete-users
```

### API Documentation

Access auto-generated documentation:

```bash
# Documentation for specific resource
GET /api/v1/users/docs

# Complete API documentation
GET /api/v1/docs
```

Returns OpenAPI 3.0 compatible JSON that can be used with Swagger UI or similar tools.

## 🧪 Testing

Run the package tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## 📝 Configuration

See the [Configuration Guide](docs/configuration.md) for detailed configuration options.

## 🔄 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## 🔒 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🙏 Credits

- [Your Name](https://github.com/yourusername)
- [All Contributors](../../contributors)

## 💡 Support

- [Documentation](https://github.com/ashique-ar/laravel-crud-generator#documentation)
- [Issues](https://github.com/ashique-ar/laravel-crud-generator/issues)
- [Discussions](https://github.com/ashique-ar/laravel-crud-generator/discussions)

---

Made with ❤️ for the Laravel community

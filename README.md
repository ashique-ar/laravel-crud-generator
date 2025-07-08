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
    // ... other configuration sections ...
    
    'resources' => [
        'users' => [
            'model' => App\Models\User::class,
            'table' => 'users',
            'fillable' => ['name', 'email', 'phone'],
            'hidden' => ['password'],
            'rules' => [
                'store' => [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'phone' => 'nullable|string|max:20'
                ],
                'update' => [
                    'name' => 'sometimes|string|max:255',
                    'email' => 'sometimes|email|unique:users,email,{{id}}',
                    'phone' => 'sometimes|string|max:20'
                ]
            ],
            'search' => [
                'enabled' => true,
                'fields' => ['name', 'email'],
                'operator' => 'like'
            ],
            'sort' => [
                'enabled' => true,
                'fields' => ['name', 'email', 'created_at'],
                'default' => ['field' => 'created_at', 'direction' => 'desc']
            ],
            'filters' => [
                'enabled' => true,
                'fields' => ['status', 'role'],
                'operators' => [
                    'status' => 'exact',
                    'role' => 'in'
                ]
            ],
            'relations' => ['profile', 'roles'],
            'permissions' => [
                'enabled' => true,
                'middleware' => 'check.crud.permission'
            ],
            'soft_deletes' => true,
            'bulk_operations' => [
                'enabled' => true,
                'operations' => ['delete', 'restore', 'update']
            ],
            'api' => [
                'paginate' => true,
                'per_page' => 15,
                'max_per_page' => 100
            ],
            'middleware' => ['auth:sanctum']
        ],
        
        // Example with model in subfolder
        'user-profiles' => [
            'model' => App\Models\User\Profile::class,
            'fillable' => ['bio', 'avatar', 'social_links'],
            'rules' => [
                'store' => [
                    'bio' => 'nullable|string|max:1000',
                    'avatar' => 'nullable|image|max:2048'
                ],
                'update' => [
                    'bio' => 'sometimes|string|max:1000',
                    'avatar' => 'sometimes|image|max:2048'
                ]
            ],
            'search' => [
                'enabled' => true,
                'fields' => ['bio']
            ]
        ],
        
        // Example with full custom namespace
        'vehicle-classes' => [
            'model' => App\Models\Vehicle\VehicleClass::class,
            'table' => 'vehicle_classes',
            'fillable' => ['name', 'description', 'is_active'],
            'rules' => [
                'store' => [
                    'name' => 'required|string|max:255|unique:vehicle_classes,name',
                    'description' => 'nullable|string|max:1000',
                    'is_active' => 'boolean',
                ],
                'update' => [
                    'name' => 'sometimes|string|max:255|unique:vehicle_classes,name,{{id}}',
                    'description' => 'sometimes|string|max:1000',
                    'is_active' => 'sometimes|boolean',
                ]
            ],
            'search' => [
                'enabled' => true,
                'fields' => ['name', 'description'],
                'operator' => 'like'
            ],
            'sort' => [
                'enabled' => true,
                'fields' => ['name', 'created_at'],
                'default' => ['field' => 'name', 'direction' => 'asc']
            ],
            'filters' => [
                'enabled' => true,
                'fields' => ['is_active'],
                'operators' => [
                    'is_active' => 'exact'
                ]
            ],
            'bulk_operations' => [
                'enabled' => true,
                'operations' => ['delete', 'update']
            ]
        ]
    ]
];
```

### 2. Register Routes

Add the CRUD routes to your `routes/api.php` file:

```php
use AshiqueAr\LaravelCrudGenerator\Facades\CrudGenerator;

// Register all CRUD routes with prefix and middleware
CrudGenerator::registerRoutes('api/v1', ['auth:sanctum']);
```

### 3. Generate Permissions

```bash
php artisan crud:permissions
```

### 4. Start Using Your API

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
    'model' => App\Models\User::class,
    'logic' => App\Services\Crud\UserLogic::class,
    'fillable' => ['name', 'email', 'created_by'],
    'rules' => [
        'store' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email'
        ],
        'update' => [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,{{id}}'
        ]
    ],
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
# Complete API documentation for all resources
GET /api/v1/docs

# Documentation for specific resource
GET /api/v1/users/docs
```

Returns OpenAPI 3.0 compatible JSON that can be used with Swagger UI or similar tools.

## 📝 Configuration

The package uses a comprehensive configuration system in `config/crud.php`. Here's a complete reference:

### Global Configuration

```php
<?php

return [
    // API defaults applied to all resources
    'api' => [
        'pagination' => [
            'enabled' => true,
            'per_page' => 15,
            'max_per_page' => 100,
        ],
        'documentation' => [
            'enabled' => true,
            'title' => 'CRUD API Documentation',
            'version' => '1.0.0',
        ],
        'response' => [
            'include_timestamps' => true,
            'include_meta' => true,
        ],
    ],

    // Permission settings (uses Spatie Laravel Permission)
    'permissions' => [
        'enabled' => true,
        'guard' => 'web',
        'format' => '{action}-{resource}',
        'actions' => ['view', 'create', 'edit', 'delete'],
        'super_admin_role' => 'super-admin',
    ],

    // Global middleware for all routes
    'middleware' => [
        // 'auth:api',
        // 'throttle:60,1',
    ],

    // Search configuration defaults
    'search' => [
        'default_operator' => 'like',
        'case_sensitive' => false,
        'operators' => [
            'like' => 'LIKE',
            'exact' => '=',
            'not_equal' => '!=',
            'greater_than' => '>',
            'less_than' => '<',
            'greater_equal' => '>=',
            'less_equal' => '<=',
            'in' => 'IN',
            'not_in' => 'NOT IN',
            'between' => 'BETWEEN',
            'starts_with' => 'LIKE',
            'ends_with' => 'LIKE',
        ],
    ],

    // Resource definitions
    'resources' => [
        // Your resources here...
    ]
];
```

### Resource Configuration Options

Each resource supports the following configuration options:

```php
'resource_name' => [
    // Required: The Eloquent model class
    'model' => App\Models\YourModel::class,
    
    // Optional: Database table name (auto-detected if not provided)
    'table' => 'your_table',
    
    // Optional: Custom logic class for business logic
    'logic' => App\Services\Crud\YourModelLogic::class,
    
    // Mass assignment protection - fields that can be filled
    'fillable' => ['field1', 'field2', 'field3'],
    
    // Fields to hide in API responses
    'hidden' => ['password', 'secret_key'],
    
    // Validation rules for different operations
    'rules' => [
        'store' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ],
        'update' => [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,{{id}}',
        ]
    ],
    
    // Search configuration
    'search' => [
        'enabled' => true,
        'fields' => ['name', 'email', 'description'],
        'operator' => 'like' // default operator for this resource
    ],
    
    // Sorting configuration
    'sort' => [
        'enabled' => true,
        'fields' => ['name', 'created_at', 'updated_at'],
        'default' => ['field' => 'created_at', 'direction' => 'desc']
    ],
    
    // Filtering configuration
    'filters' => [
        'enabled' => true,
        'fields' => ['status', 'category_id', 'is_active'],
        'operators' => [
            'status' => 'exact',
            'category_id' => 'in',
            'is_active' => 'exact'
        ]
    ],
    
    // Relationships to eager load
    'relations' => ['category', 'tags', 'author'],
    
    // Permission settings for this resource
    'permissions' => [
        'enabled' => true,
        'middleware' => 'check.crud.permission'
    ],
    
    // Soft deletes support
    'soft_deletes' => true, // Set to true if model uses SoftDeletes trait
    
    // Bulk operations configuration
    'bulk_operations' => [
        'enabled' => true,
        'operations' => ['delete', 'restore', 'update'] // Available operations
    ],
    
    // API-specific settings for this resource
    'api' => [
        'paginate' => true,
        'per_page' => 20,      // Override global default
        'max_per_page' => 50   // Override global default
    ],
    
    // Additional middleware for this resource only
    'middleware' => ['auth:sanctum', 'role:admin']
],
```

### Validation Rule Placeholders

In validation rules, you can use placeholders that will be replaced during validation:

- `{{id}}` - The ID of the current resource (useful for unique rules in updates)
- `{{user_id}}` - The ID of the authenticated user
- `{{resource}}` - The name of the current resource

Example:
```php
'rules' => [
    'update' => [
        'email' => 'required|email|unique:users,email,{{id}}',
        'slug' => 'required|string|unique:posts,slug,{{id}}'
    ]
]
```

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

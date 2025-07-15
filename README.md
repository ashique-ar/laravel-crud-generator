# Laravel CRUD Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ashique-ar/laravel-crud-generator.svg?style=flat-square)](https://packagist.org/packages/ashique-ar/laravel-crud-generator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ashique-ar/laravel-crud-generator/run-tests?label=tests)](https://github.com/ashique-ar/laravel-crud-generator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ashique-ar/laravel-crud-generator/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ashique-ar/laravel-crud-generator/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ashique-ar/laravel-crud-generator.svg?style=flat-square)](https://packagist.org/packages/ashique-ar/laravel-crud-generator)

A powerful, configuration-driven CRUD API generator for Laravel applications. Generate complete REST APIs with advanced features like permissions, filtering, sorting, validation, relations management, and custom business logic handlers - all through simple configuration.

## ✨ Features

- **🚀 Zero-Code CRUD APIs** - Generate complete REST APIs through configuration
- **� Advanced Relations Management** - Dynamic form relations with dependent dropdowns
- **�🔐 Permission Integration** - Built-in support for Spatie Laravel Permission
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

### 1. Generate Your First Resource

Use the artisan command to quickly scaffold a new resource:

```bash
# Generate a basic resource
php artisan make:crud-resource users --model=App\\Models\\User

# Generate with additional options
php artisan make:crud-resource posts --model=App\\Models\\Post --logic --resource --permissions
```

### 2. Configure Relations (New!)

Add relations to enable dynamic form management:

```bash
# Interactive mode to configure relations
php artisan crud:relations posts --interactive

# Add specific relations
php artisan crud:relations posts --field=category_id --entity=categories --type=single --searchable
```

### 3. Example Resource Configuration

Here's a complete example of a resource with relations in `config/crud.php`:

```php
<?php

return [
    // ... other configuration sections ...
    
    'resources' => [
        'vehicles' => [
            'model' => App\Models\Vehicle::class,
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'fillable' => ['name', 'make_id', 'model_id', 'year', 'color', 'vin'],
            'hidden' => ['internal_notes', 'cost_price'],
            'rules' => [
                'store' => [
                    'name' => 'required|string|max:255',
                    'make_id' => 'required|exists:vehicle_makes,id',
                    'model_id' => 'required|exists:vehicle_models,id',
                ],
                'update' => [
                    'name' => 'sometimes|required|string|max:255',
                    'make_id' => 'sometimes|required|exists:vehicle_makes,id',
                    'model_id' => 'sometimes|required|exists:vehicle_models,id',
                ]
            ],
            'pagination' => [
                'per_page' => 15,
                'max_per_page' => 100,
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at', 'updated_at'],
            'filterable_fields' => ['make_id', 'model_id'],
            'relationships' => [
                'make_id' => [
                    'entity' => 'vehicle-makes',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'displayField' => 'name',
                    'searchable' => true,
                    'required' => true,
                ],
                'model_id' => [
                    'entity' => 'vehicle-models',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'displayField' => 'name',
                    'searchable' => true,
                    'required' => true,
                    'depends_on' => 'make_id',
                    'filter_by' => 'make_id',
                ],
            ],
            'soft_deletes' => false,
        ],
    ],
];
```

### 4. Register API Routes

Add to your `routes/api.php` or service provider:

```php
use AshiqueAr\LaravelCrudGenerator\Facades\CrudGenerator;

// Register all CRUD routes with middleware
CrudGenerator::registerRoutes('api/v1', ['auth:sanctum']);

// Or register specific resources
CrudGenerator::registerRoutes('api/v1', ['auth:sanctum'], ['users', 'posts']);
```

Your API endpoints will be available at:
- `GET /api/v1/vehicles` - List vehicles with pagination, filtering, sorting
- `POST /api/v1/vehicles` - Create a new vehicle
- `GET /api/v1/vehicles/{id}` - Get a specific vehicle
- `PUT /api/v1/vehicles/{id}` - Update a vehicle
- `DELETE /api/v1/vehicles/{id}` - Delete a vehicle
- `GET /api/v1/vehicles/docs` - API documentation for vehicles resource

## 🛠️ Artisan Commands

### Resource Management

```bash
# Generate a new CRUD resource
php artisan make:crud-resource {name} --model={Model}

# Examples:
php artisan make:crud-resource users --model=App\\Models\\User
php artisan make:crud-resource posts --model=App\\Models\\Blog\\Post --logic --resource --permissions
php artisan make:crud-resource website_settings --model=App\\Models\\Website\\WebsiteSetting
```

**Options:**
- `--model=` : Specify the model class
- `--logic` : Generate a custom logic class
- `--resource` : Generate an API resource class
- `--permissions` : Generate permissions for this resource
- `--force` : Overwrite existing files

### Relations Management

```bash
# Interactive mode to configure relationships
php artisan crud:relations {resource} --interactive

# Add a specific relationship
php artisan crud:relations {resource} --field={field} --entity={entity}

# Examples:
php artisan crud:relations vehicles --interactive
php artisan crud:relations posts --field=category_id --entity=categories --searchable --nullable
php artisan crud:relations vehicles --field=model_id --entity=vehicle-models --depends-on=make_id --filter-by=make_id
```

**Options:**
- `--field=` : Field name (e.g., category_id)
- `--entity=` : Target entity name (e.g., categories)
- `--label-field=` : Label field (default: name)
- `--value-field=` : Value field (default: id)
- `--display-field=` : Display field for tables (default: same as label-field)
- `--searchable` : Make the relationship searchable
- `--nullable` : Make the relationship optional (not required)
- `--depends-on=` : Field this relationship depends on
- `--filter-by=` : Field to filter by when depends-on is set
- `--interactive` : Interactive mode

### Custom Logic

```bash
# Generate custom logic class
php artisan make:crud-logic {name} --model={Model}

# Examples:
php artisan make:crud-logic UserLogic --model=App\\Models\\User
php artisan make:crud-logic PostLogic --model=App\\Models\\Post --force
```

### Permissions

```bash
# Generate permissions for resources
php artisan crud:permissions --resource={resource}

# Examples:
php artisan crud:permissions --resource=users
php artisan crud:permissions --resource=posts
```

### Installation & Setup

```bash
# Install the CRUD generator
php artisan crud:install

# This will:
# - Publish config/crud.php
# - Set up middleware
# - Register routes (optional)
# - Generate permissions (optional)
```

## 🔗 Relationships Configuration

The relationships system allows you to create dynamic forms with dependent dropdowns and complex relationships. This unified approach handles both Laravel Eloquent relationships and form field relationships in one configuration.

### Relationship Types

1. **Single Select** - For foreign key relationships (belongs to)
2. **Multiple Select** - For many-to-many relationships (coming soon)
3. **Dependent Dropdowns** - Where one field depends on another

### Relationship Configuration Structure

```php
'relationships' => [
    'field_name' => [
        'entity' => 'target-entity-name',        // Kebab-case entity name
        'labelField' => 'name',                  // Field to display as label
        'valueField' => 'id',                    // Field to use as value
        'displayField' => 'name',                // Field to show in tables/lists
        'searchable' => true,                    // Enable search in dropdown
        'required' => false,                     // Whether field is required
        'depends_on' => 'parent_field',          // Optional: parent field for dependencies
        'filter_by' => 'parent_field',           // Optional: filter records by parent field
    ],
],
```

### Real-World Examples

#### Vehicle Management System

```php
'vehicles' => [
    'model' => App\Models\Vehicle::class,
    'fillable' => ['name', 'class_id', 'make_id', 'model_id', 'year', 'color', 'vin'],
    'hidden' => ['internal_notes', 'cost_price'],
    'relationships' => [
        'class_id' => [
            'entity' => 'vehicle-classes',
            'labelField' => 'name',
            'valueField' => 'id',
            'displayField' => 'name',
            'searchable' => true,
            'required' => true,
        ],
        'make_id' => [
            'entity' => 'vehicle-makes',
            'labelField' => 'name',
            'valueField' => 'id',
            'displayField' => 'name',
            'searchable' => true,
            'required' => true,
        ],
        'model_id' => [
            'entity' => 'vehicle-models',
            'labelField' => 'name',
            'valueField' => 'id',
            'displayField' => 'name',
            'searchable' => true,
            'required' => true,
            'depends_on' => 'make_id',
            'filter_by' => 'make_id',
        ],
    ],
],
```

#### Blog Post with Tags

```php
'posts' => [
    'model' => App\Models\Post::class,
    'fillable' => ['title', 'content', 'category_id', 'slug', 'excerpt', 'featured_image'],
    'hidden' => ['internal_notes', 'admin_notes'],
    'relationships' => [
        'category_id' => [
            'entity' => 'categories',
            'labelField' => 'name',
            'valueField' => 'id',
            'displayField' => 'name',
            'searchable' => true,
            'required' => true,
        ],
        'author_id' => [
            'entity' => 'users',
            'labelField' => 'name',
            'valueField' => 'id',
            'displayField' => 'email',
            'searchable' => true,
            'required' => true,
        ],
    ],
],
```

### Frontend Integration

Your dynamic frontend can read these configurations and automatically:

1. **Generate appropriate form controls** based on relationship configuration
2. **Fetch options** from the appropriate endpoints (`/api/crud/{entity}`)
3. **Handle dependent dropdowns** by watching parent field changes
4. **Support search functionality** in dropdowns
5. **Validate required/optional** fields
6. **Display appropriate fields** in tables and lists

Example frontend usage:
```javascript
// Fetch resource configuration
const config = await fetch('/api/v1/vehicles/config');
const relationships = config.relationships;

// Generate form fields based on relationships
Object.keys(relationships).forEach(fieldName => {
    const relationship = relationships[fieldName];
    
    // Create select field
    createSelectField(fieldName, relationship);
    
    // Handle dependencies
    if (relationship.depends_on) {
        watchFieldChanges(relationship.depends_on, fieldName, relationship.filter_by);
    }
});

// Example function to fetch dependent options
async function fetchDependentOptions(entity, filterBy, filterValue) {
    const response = await fetch(`/api/crud/${entity}?filter[${filterBy}]=${filterValue}`);
    return response.json();
}
```

## 📊 API Endpoints

All resources automatically get the following endpoints:

### Standard CRUD Operations

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/{resource}` | List all items with pagination, filtering, sorting |
| `POST` | `/api/v1/{resource}` | Create a new item |
| `GET` | `/api/v1/{resource}/{id}` | Get a specific item |
| `PUT` | `/api/v1/{resource}/{id}` | Update an item |
| `DELETE` | `/api/v1/{resource}/{id}` | Delete an item |

### Additional Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/{resource}/config` | Get resource configuration (including relations) |
| `GET` | `/api/v1/{resource}/docs` | API documentation for this resource |
| `POST` | `/api/v1/{resource}/bulk` | Bulk operations (delete, update) |
| `POST` | `/api/v1/{resource}/{id}/restore` | Restore soft-deleted item |

### Query Parameters

#### Pagination
```
GET /api/v1/users?page=2&per_page=20
```

#### Search
```
GET /api/v1/users?search=john
GET /api/v1/users?search[name]=john&search[email]=doe
```

#### Sorting
```
GET /api/v1/users?sort=name&direction=asc
GET /api/v1/users?sort[]=name:asc&sort[]=created_at:desc
```

#### Filtering
```
GET /api/v1/users?filter[status]=active
GET /api/v1/users?filter[role]=admin,manager
```

#### Including Relations
```
GET /api/v1/users?include=profile,roles
```

## ⚙️ Configuration Options

### Global Configuration

Edit `config/crud.php` to customize global settings:

```php
return [
    'api' => [
        'pagination' => [
            'enabled' => true,
            'per_page' => 15,
            'max_per_page' => 100,
        ],
        'prefix' => 'api/crud',
        'documentation' => [
            'enabled' => true,
            'title' => 'CRUD API Documentation',
            'version' => '1.0.0',
        ],
    ],
    
    'permissions' => [
        'enabled' => true,
        'guard' => 'web',
        'format' => '{action}-{resource}', // view-users, create-posts, etc.
        'actions' => ['view', 'create', 'edit', 'delete'],
        'super_admin_role' => 'super-admin',
    ],
    
    'search' => [
        'default_operator' => 'like',
        'case_sensitive' => false,
        'operators' => [
            'like' => 'LIKE',
            'exact' => '=',
            'not_equal' => '!=',
            'greater_than' => '>',
            'less_than' => '<',
            // ... more operators
        ],
    ],
    
    'resources' => [
        // Your resource definitions
    ],
    
    'add_new_resource_to' => 'bottom', // or 'top'
];
```

### Resource Configuration Options

Each resource supports these configuration options:

```php
'resource-name' => [
    // Core settings
    'model' => App\Models\ResourceModel::class,
    'middleware' => ['auth:sanctum', 'crud.permissions'],
    
    'fillable' => [...],
    'hidden' => [...],
    // Validation rules
    'rules' => [
        'store' => [...],
        'update' => [...],
    ],
    
    // Pagination settings
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],
    
    // Search configuration
    'searchable_fields' => ['name', 'email'],
    
    // Sorting configuration
    'sortable_fields' => ['id', 'name', 'created_at', 'updated_at'],
    
    // Filtering configuration
    'filterable_fields' => ['status', 'category_id'],
    
    // Relationships for dynamic forms and Eloquent relationships
    'relationships' => [
        'category_id' => [
            'entity' => 'categories',
            'labelField' => 'name',
            'valueField' => 'id',
            'displayField' => 'name',
            'searchable' => true,
            'required' => false,
            'depends_on' => null,
            'filter_by' => null,
        ],
        // See relationships section for more details
    ],
    
    // Soft deletes support
    'soft_deletes' => false,
],
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
    'fillable' => ['name', 'email', 'phone'],
    'hidden' => ['password'],
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
        // Your resource configurations here
    ],

    // Where to add new resources when using make:crud-resource command
    'add_new_resource_to' => 'bottom', // 'top' or 'bottom'
];
```

## 🧪 Testing

The package includes comprehensive tests. Run them using:

```bash
# Run all tests
./vendor/bin/phpunit

# Run with coverage
./vendor/bin/phpunit --coverage-text

# Run specific test file
./vendor/bin/phpunit tests/Feature/CrudApiTest.php
```

### Example Test

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class UserCrudTest extends TestCase
{
    public function test_can_list_users()
    {
        $users = User::factory()->count(5)->create();
        
        $response = $this->get('/api/v1/users');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'email', 'created_at', 'updated_at']
                    ],
                    'meta' => ['current_page', 'total', 'per_page']
                ]);
    }

    public function test_can_create_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $response = $this->post('/api/v1/users', $userData);

        $response->assertStatus(201)
                ->assertJson(['data' => $userData]);
                
        $this->assertDatabaseHas('users', $userData);
    }
}
```

## 🔒 Security

### Permission Middleware

The package includes built-in permission checking:

```php
// Applied automatically to all CRUD routes
'middleware' => ['auth:sanctum', 'crud.permissions']
```

This checks for permissions like:
- `view-users` for GET requests
- `create-users` for POST requests  
- `edit-users` for PUT/PATCH requests
- `delete-users` for DELETE requests

### Validation

All requests are automatically validated using the rules defined in your configuration:

```php
'rules' => [
    'store' => [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
    ],
    'update' => [
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,{{id}}',
    ]
]
```

Use `{{id}}` in update rules to exclude the current record from unique checks.

### Mass Assignment Protection

Only fields defined in `fillable` arrays are allowed for mass assignment, providing protection against mass assignment vulnerabilities.

## 📚 Examples

### Complete Vehicle Management System

See `examples/vehicle-crud-config.php` for a complete example of a vehicle management system with:
- Vehicle classes, makes, models
- Dependent dropdowns (model depends on make)
- Complex relationships
- Full CRUD operations

### Blog System

```php
'resources' => [
    'categories' => [
        'model' => App\Models\Category::class,
        'fillable' => ['name', 'slug', 'description'],
        'hidden' => ['internal_notes'],
        'rules' => [
            'store' => ['name' => 'required|string|max:255|unique:categories'],
            'update' => ['name' => 'sometimes|required|string|max:255|unique:categories,name,{{id}}'],
        ],
        'searchable_fields' => ['name'],
        'sortable_fields' => ['id', 'name', 'created_at'],
    ],
    
    'posts' => [
        'model' => App\Models\Post::class,
        'fillable' => ['title', 'content', 'category_id', 'status', 'slug', 'excerpt', 'featured_image'],
        'hidden' => ['internal_notes', 'admin_notes'],
        'rules' => [
            'store' => [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'status' => 'required|in:draft,published',
            ],
            'update' => [
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'category_id' => 'sometimes|required|exists:categories,id',
                'status' => 'sometimes|required|in:draft,published',
            ],
        ],
        'searchable_fields' => ['title', 'content'],
        'sortable_fields' => ['id', 'title', 'created_at', 'updated_at'],
        'filterable_fields' => ['status', 'category_id'],
        'relationships' => [
            'category_id' => [
                'entity' => 'categories',
                'labelField' => 'name',
                'valueField' => 'id',
                'displayField' => 'name',
                'searchable' => true,
                'required' => true,
            ],
        ],
        'soft_deletes' => true,
    ],
],
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/ashique-ar/laravel-crud-generator.git

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit

# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🆘 Support

- **Documentation**: This README and inline documentation
- **Issues**: [GitHub Issues](https://github.com/ashique-ar/laravel-crud-generator/issues)
- **Discussions**: [GitHub Discussions](https://github.com/ashique-ar/laravel-crud-generator/discussions)

## 🗺️ Roadmap

- [ ] GraphQL support
- [ ] Real-time updates with WebSockets
- [ ] Advanced caching strategies
- [ ] Export functionality (CSV, Excel, PDF)
- [ ] Import functionality with validation
- [ ] Audit logging
- [ ] API rate limiting per resource
- [ ] Resource versioning
- [ ] Custom field types and validation rules

---

**Made with ❤️ by [Ashique AR](https://github.com/ashique-ar)**
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
    'filterable_fields' => ['status', 'category_id', 'is_active'],
    
    // Relationships configuration (handles both form relations and Eloquent relationships)
    'relationships' => [
        'user_id' => [
            'entity' => 'users',
            'labelField' => 'name',
            'valueField' => 'id',
            'displayField' => 'email',
            'searchable' => true,
            'required' => true,
        ],
        'category_id' => [
            'entity' => 'categories',
            'labelField' => 'name',
            'valueField' => 'id',
            'displayField' => 'name',
            'searchable' => true,
            'required' => false,
            'depends_on' => 'parent_category_id',
            'filter_by' => 'parent_id',
        ],
    ],
    
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

- [Your Name](https://github.com/ashique-ar)
- [All Contributors](../../contributors)

## 💡 Support

- [Documentation](https://github.com/ashique-ar/laravel-crud-generator#documentation)
- [Issues](https://github.com/ashique-ar/laravel-crud-generator/issues)
- [Discussions](https://github.com/ashique-ar/laravel-crud-generator/discussions)

---

Made with ❤️ for the Laravel community

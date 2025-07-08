<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel CRUD Generator Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Laravel CRUD Generator package.
    | Here you can define all your resources, their models, validation rules,
    | permissions, and other CRUD-related settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default API Settings
    |--------------------------------------------------------------------------
    |
    | These settings apply to all CRUD endpoints unless overridden
    | at the resource level.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Permission Settings
    |--------------------------------------------------------------------------
    |
    | Configure how permissions are handled across all resources.
    | Uses Spatie Laravel Permission package.
    |
    */
    'permissions' => [
        'enabled' => true,
        'guard' => 'web',
        'format' => '{action}-{resource}',
        'actions' => ['view', 'create', 'edit', 'delete'],
        'super_admin_role' => 'super-admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware that will be applied to all CRUD routes.
    | Individual resources can add additional middleware.
    |
    */
    'middleware' => [
        // 'auth:api',
        // 'throttle:60,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Global search settings that apply to all resources.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Resource Definitions
    |--------------------------------------------------------------------------
    |
    | Define all your CRUD resources here. Each resource represents a model
    | and its associated CRUD operations, validation rules, permissions, etc.
    |
    | Example resource configuration:
    |
    | 'users' => [
    |     'model' => App\Models\User::class,
    |     'table' => 'users',
    |     'logic' => App\Services\Crud\UserLogic::class,
    |     'fillable' => ['name', 'email', 'phone'],
    |     'hidden' => ['password'],
    |     'validation' => [
    |         'name' => 'required|string|max:255',
    |         'email' => 'required|email|unique:users,email',
    |     ],
    |     'search' => [
    |         'enabled' => true,
    |         'fields' => ['name', 'email'],
    |         'operator' => 'like'
    |     ],
    |     'sort' => [
    |         'enabled' => true,
    |         'fields' => ['name', 'email', 'created_at'],
    |         'default' => ['field' => 'created_at', 'direction' => 'desc']
    |     ],
    |     'relations' => ['profile', 'roles'],
    |     'permissions' => [
    |         'enabled' => true,
    |         'middleware' => 'check.crud.permission'
    |     ],
    |     'soft_deletes' => true,
    |     'bulk_operations' => [
    |         'enabled' => true,
    |         'operations' => ['delete', 'restore', 'update']
    |     ],
    |     'api' => [
    |         'paginate' => true,
    |         'per_page' => 15,
    |         'max_per_page' => 100
    |     ],
    |     'middleware' => ['auth:api']
    | ],
    |
    */
    'resources' => [
        // Define your resources here
        // Example:
        // 'users' => [
        //     'model' => App\Models\User::class,
        //     'fillable' => ['name', 'email'],
        //     'validation' => [
        //         'name' => 'required|string|max:255',
        //         'email' => 'required|email|unique:users,email',
        //     ],
        //     'search' => [
        //         'enabled' => true,
        //         'fields' => ['name', 'email']
        //     ],
        //     'permissions' => ['enabled' => true],
        // ],
    ],
];

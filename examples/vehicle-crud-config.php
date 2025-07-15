<?php

return [
    // ... other configuration ...

    'resources' => [
        'vehicles' => [
            'model' => 'App\\Models\\Vehicle',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'rules' => [
                'store' => [
                    'name' => 'required|string|max:255',
                    'class_id' => 'required|exists:vehicle_classes,id',
                    'fuel_type_id' => 'required|exists:vehicle_fuel_types,id',
                    'category_id' => 'required|exists:vehicle_categories,id',
                    'make_id' => 'required|exists:vehicle_makes,id',
                    'model_id' => 'required|exists:vehicle_models,id',
                ],
                'update' => [
                    'name' => 'sometimes|required|string|max:255',
                    'class_id' => 'sometimes|required|exists:vehicle_classes,id',
                    'fuel_type_id' => 'sometimes|required|exists:vehicle_fuel_types,id',
                    'category_id' => 'sometimes|required|exists:vehicle_categories,id',
                    'make_id' => 'sometimes|required|exists:vehicle_makes,id',
                    'model_id' => 'sometimes|required|exists:vehicle_models,id',
                ],
            ],
            'pagination' => [
                'per_page' => 15,
                'max_per_page' => 100,
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at', 'updated_at'],
            'filterable_fields' => ['class_id', 'fuel_type_id', 'category_id', 'make_id'],
            'relations' => [
                'class_id' => [
                    'entity' => 'vehicle-classes',
                    'endpoint' => '/api/crud/vehicle-classes',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'type' => 'single',
                    'searchable' => true,
                    'nullable' => false,
                ],
                'fuel_type_id' => [
                    'entity' => 'vehicle-fuel-types',
                    'endpoint' => '/api/crud/vehicle-fuel-types',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'type' => 'single',
                    'searchable' => true,
                    'nullable' => false,
                ],
                'category_id' => [
                    'entity' => 'vehicle-categories',
                    'endpoint' => '/api/crud/vehicle-categories',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'type' => 'single',
                    'searchable' => true,
                    'nullable' => false,
                ],
                'make_id' => [
                    'entity' => 'vehicle-makes',
                    'endpoint' => '/api/crud/vehicle-makes',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'type' => 'single',
                    'searchable' => true,
                    'nullable' => false,
                ],
                'model_id' => [
                    'entity' => 'vehicle-models',
                    'endpoint' => '/api/crud/vehicle-models',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'type' => 'single',
                    'searchable' => true,
                    'nullable' => false,
                    'dependsOn' => 'make_id', // Model depends on make selection
                ],
            ],
            'relationships' => ['vehicleClass', 'fuelType', 'category', 'make', 'model'], // Laravel Eloquent relationships
            'soft_deletes' => false,
        ],

        // Supporting resources
        'vehicle-classes' => [
            'model' => 'App\\Models\\VehicleClass',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'rules' => [
                'store' => ['name' => 'required|string|max:255|unique:vehicle_classes'],
                'update' => ['name' => 'sometimes|required|string|max:255|unique:vehicle_classes,name,{{id}}'],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'relations' => [],
            'relationships' => [],
        ],

        'vehicle-fuel-types' => [
            'model' => 'App\\Models\\VehicleFuelType',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'rules' => [
                'store' => ['name' => 'required|string|max:255|unique:vehicle_fuel_types'],
                'update' => ['name' => 'sometimes|required|string|max:255|unique:vehicle_fuel_types,name,{{id}}'],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'relations' => [],
            'relationships' => [],
        ],

        'vehicle-categories' => [
            'model' => 'App\\Models\\VehicleCategory',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'rules' => [
                'store' => ['name' => 'required|string|max:255|unique:vehicle_categories'],
                'update' => ['name' => 'sometimes|required|string|max:255|unique:vehicle_categories,name,{{id}}'],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'relations' => [],
            'relationships' => [],
        ],

        'vehicle-makes' => [
            'model' => 'App\\Models\\VehicleMake',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'rules' => [
                'store' => ['name' => 'required|string|max:255|unique:vehicle_makes'],
                'update' => ['name' => 'sometimes|required|string|max:255|unique:vehicle_makes,name,{{id}}'],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'relations' => [],
            'relationships' => [],
        ],

        'vehicle-models' => [
            'model' => 'App\\Models\\VehicleModel',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'rules' => [
                'store' => [
                    'name' => 'required|string|max:255',
                    'make_id' => 'required|exists:vehicle_makes,id',
                ],
                'update' => [
                    'name' => 'sometimes|required|string|max:255',
                    'make_id' => 'sometimes|required|exists:vehicle_makes,id',
                ],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'filterable_fields' => ['make_id'],
            'relations' => [
                'make_id' => [
                    'entity' => 'vehicle-makes',
                    'endpoint' => '/api/crud/vehicle-makes',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'type' => 'single',
                    'searchable' => true,
                    'nullable' => false,
                ],
            ],
            'relationships' => ['make'],
        ],
    ],

    'add_new_resource_to' => 'bottom',
];

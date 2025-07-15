<?php

return [
    // ... other configuration ...

    'resources' => [
        'vehicles' => [
            'model' => 'App\\Models\\Vehicle',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'fillable' => ['name', 'class_id', 'fuel_type_id', 'category_id', 'make_id', 'model_id', 'year', 'color', 'vin', 'license_plate'],
            'hidden' => ['internal_notes', 'cost_price', 'purchase_date_internal'],
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
            'relationships' => [
                'class_id' => [
                    'entity' => 'vehicle-classes',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'displayField' => 'name',
                    'searchable' => true,
                    'required' => true,
                ],
                'fuel_type_id' => [
                    'entity' => 'vehicle-fuel-types',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'displayField' => 'name',
                    'searchable' => true,
                    'required' => true,
                ],
                'category_id' => [
                    'entity' => 'vehicle-categories',
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
            'soft_deletes' => false,
        ],

        // Supporting resources
        'vehicle-classes' => [
            'model' => 'App\\Models\\VehicleClass',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'fillable' => ['name', 'description'],
            'hidden' => [],
            'rules' => [
                'store' => ['name' => 'required|string|max:255|unique:vehicle_classes'],
                'update' => ['name' => 'sometimes|required|string|max:255|unique:vehicle_classes,name,{{id}}'],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'relationships' => [],
        ],

        'vehicle-fuel-types' => [
            'model' => 'App\\Models\\VehicleFuelType',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'fillable' => ['name', 'description'],
            'hidden' => [],
            'rules' => [
                'store' => ['name' => 'required|string|max:255|unique:vehicle_fuel_types'],
                'update' => ['name' => 'sometimes|required|string|max:255|unique:vehicle_fuel_types,name,{{id}}'],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'relationships' => [],
        ],

        'vehicle-categories' => [
            'model' => 'App\\Models\\VehicleCategory',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'fillable' => ['name', 'description'],
            'hidden' => [],
            'rules' => [
                'store' => ['name' => 'required|string|max:255|unique:vehicle_categories'],
                'update' => ['name' => 'sometimes|required|string|max:255|unique:vehicle_categories,name,{{id}}'],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'relationships' => [],
        ],

        'vehicle-makes' => [
            'model' => 'App\\Models\\VehicleMake',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'fillable' => ['name', 'description', 'logo'],
            'hidden' => ['internal_code'],
            'rules' => [
                'store' => ['name' => 'required|string|max:255|unique:vehicle_makes'],
                'update' => ['name' => 'sometimes|required|string|max:255|unique:vehicle_makes,name,{{id}}'],
            ],
            'searchable_fields' => ['name'],
            'sortable_fields' => ['id', 'name', 'created_at'],
            'relationships' => [],
        ],

        'vehicle-models' => [
            'model' => 'App\\Models\\VehicleModel',
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'fillable' => ['name', 'make_id', 'year_start', 'year_end'],
            'hidden' => ['internal_code'],
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
            'relationships' => [
                'make_id' => [
                    'entity' => 'vehicle-makes',
                    'labelField' => 'name',
                    'valueField' => 'id',
                    'displayField' => 'name',
                    'searchable' => true,
                    'required' => true,
                ],
            ],
        ],
    ],

    'add_new_resource_to' => 'bottom',
];

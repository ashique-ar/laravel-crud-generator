<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Services\Crud;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Base class for custom CRUD logic handlers.
 * 
 * This class provides default implementations for all CRUD lifecycle hooks
 * and can be extended to implement custom business logic for specific resources.
 * 
 * @package AshiqueAr\LaravelCrudGenerator\Services\Crud
 */
class BaseCrudLogic
{
    /**
     * The model class this logic handler manages.
     */
    protected string $modelClass;

    /**
     * Set the model class for this logic handler.
     */
    public function setModelClass(string $modelClass): void
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Get the model class for this logic handler.
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Apply custom query modifications before listing resources.
     * 
     * This method is called before the index query is executed.
     * Use it to apply default filters, scopes, or other query modifications.
     * 
     * @param Builder $query The query builder instance
     * @param Request $request The incoming request
     * @return Builder The modified query builder
     */
    public function beforeIndex(Builder $query, Request $request): Builder
    {
        return $query;
    }

    /**
     * Apply custom logic before creating a resource.
     * 
     * This method is called before validation and model creation.
     * Use it to transform data, set defaults, or add computed values.
     * 
     * @param array $data The request data
     * @param Request $request The incoming request
     * @return array The modified data array
     */
    public function beforeCreate(array $data, Request $request): array
    {
        return $data;
    }

    /**
     * Apply custom logic after creating a resource.
     * 
     * This method is called after the model has been successfully created.
     * Use it for follow-up actions like sending notifications, logging, etc.
     * 
     * @param Model $model The newly created model
     * @param Request $request The incoming request
     */
    public function afterCreate(Model $model, Request $request): void
    {
        // Override in child classes if needed
    }

    /**
     * Apply custom logic before updating a resource.
     * 
     * This method is called before validation and model update.
     * Use it to transform data or add computed values.
     * 
     * @param array $data The request data
     * @param Model $model The model being updated
     * @param Request $request The incoming request
     * @return array The modified data array
     */
    public function beforeUpdate(array $data, Model $model, Request $request): array
    {
        return $data;
    }

    /**
     * Apply custom logic after updating a resource.
     * 
     * This method is called after the model has been successfully updated.
     * Use it for follow-up actions like cache clearing, notifications, etc.
     * 
     * @param Model $model The updated model
     * @param Request $request The incoming request
     */
    public function afterUpdate(Model $model, Request $request): void
    {
        // Override in child classes if needed
    }

    /**
     * Apply custom logic before deleting a resource.
     * 
     * This method is called before the model is deleted.
     * Return false to prevent deletion.
     * 
     * @param Model $model The model being deleted
     * @param Request $request The incoming request
     * @return bool True to allow deletion, false to prevent it
     */
    public function beforeDelete(Model $model, Request $request): bool
    {
        return true;
    }

    /**
     * Apply custom logic after deleting a resource.
     * 
     * This method is called after the model has been successfully deleted.
     * Use it for cleanup actions, notifications, etc.
     * 
     * @param Model $model The deleted model
     * @param Request $request The incoming request
     */
    public function afterDelete(Model $model, Request $request): void
    {
        // Override in child classes if needed
    }

    /**
     * Get additional validation rules for this resource.
     * 
     * These rules will be merged with the rules from config/crud.php.
     * 
     * @param Request $request The incoming request
     * @param Model|null $model The model being updated (null for create)
     * @return array Additional validation rules
     */
    public function getValidationRules(Request $request, ?Model $model = null): array
    {
        return [];
    }

    /**
     * Get custom error messages for validation.
     * 
     * @return array Custom validation error messages
     */
    public function getValidationMessages(): array
    {
        return [];
    }

    /**
     * Transform data before returning in API responses.
     * 
     * This method is called before sending the model data in API responses.
     * Use it to add computed fields, format data, or hide sensitive information.
     * 
     * @param Model $model The model to transform
     * @param Request $request The incoming request
     * @return array The transformed data
     */
    public function transformResource(Model $model, Request $request): array
    {
        return $model->toArray();
    }

    /**
     * Apply custom logic before restoring a soft-deleted resource.
     * 
     * @param Model $model The model being restored
     * @param Request $request The incoming request
     * @return bool True to allow restoration, false to prevent it
     */
    public function beforeRestore(Model $model, Request $request): bool
    {
        return true;
    }

    /**
     * Apply custom logic after restoring a soft-deleted resource.
     * 
     * @param Model $model The restored model
     * @param Request $request The incoming request
     */
    public function afterRestore(Model $model, Request $request): void
    {
        // Override in child classes if needed
    }

    /**
     * Apply custom logic for bulk operations.
     * 
     * This method is called before each model in a bulk operation.
     * 
     * @param string $operation The bulk operation (delete, update, restore, etc.)
     * @param Model $model The model being processed
     * @param array $data The data for the operation (for update operations)
     * @param Request $request The incoming request
     * @return bool True to allow the operation, false to skip this model
     */
    public function beforeBulkOperation(string $operation, Model $model, array $data, Request $request): bool
    {
        return true;
    }

    /**
     * Apply custom logic after a bulk operation.
     * 
     * @param string $operation The bulk operation that was performed
     * @param array $models The models that were processed
     * @param Request $request The incoming request
     */
    public function afterBulkOperation(string $operation, array $models, Request $request): void
    {
        // Override in child classes if needed
    }

    /**
     * Determine if a field should be hidden from API responses.
     * 
     * @param string $field The field name
     * @param Model $model The model instance
     * @param Request $request The incoming request
     * @return bool True if the field should be hidden
     */
    public function shouldHideField(string $field, Model $model, Request $request): bool
    {
        return false;
    }

    /**
     * Get computed fields to add to API responses.
     * 
     * @param Model $model The model instance
     * @param Request $request The incoming request
     * @return array Computed fields as key-value pairs
     */
    public function getComputedFields(Model $model, Request $request): array
    {
        return [];
    }

    /**
     * Apply custom authorization logic beyond permissions.
     * 
     * This method is called after permission checks pass.
     * Use it for additional authorization logic like ownership checks.
     * 
     * @param string $action The action being performed (view, create, edit, delete)
     * @param Model|null $model The model being accessed (null for index/create)
     * @param Request $request The incoming request
     * @return bool True if authorized, false otherwise
     */
    public function authorize(string $action, ?Model $model, Request $request): bool
    {
        return true;
    }

    /**
     * Handle custom CRUD operations not covered by standard CRUD.
     * 
     * @param string $operation The custom operation name
     * @param array $parameters The operation parameters
     * @param Request $request The incoming request
     * @return mixed The operation result
     */
    public function handleCustomOperation(string $operation, array $parameters, Request $request)
    {
        throw new \BadMethodCallException("Custom operation '{$operation}' is not implemented");
    }
}



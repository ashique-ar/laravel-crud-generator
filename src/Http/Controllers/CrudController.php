<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Http\Controllers;

use AshiqueAr\LaravelCrudGenerator\Exceptions\CrudException;
use AshiqueAr\LaravelCrudGenerator\Services\Crud\BaseCrudLogic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Throwable;

/**
 * Generic CRUD Controller for API endpoints.
 *
 * This controller provides a unified interface for CRUD operations across different models.
 * Configuration is loaded from config/crud.php based on the route resource parameter.
 *
 * Features:
 * - Automatic CRUD operations (index, show, store, update, destroy)
 * - Permission-based authorization using Spatie Laravel Permission
 * - Custom logic handlers for advanced business logic
 * - Eager loading relationships
 * - Advanced filtering, searching, and sorting
 * - Bulk operations
 * - Soft delete handling
 * - API documentation generation
 */
class CrudController extends Controller
{
    /**
     * The fully qualified class name of the model.
     */
    protected string $modelClass = '';

    /**
     * Validation rules for operations.
     */
    protected array $validationRules = [];

    /**
     * Relationships to eager load.
     */
    protected array $relations = [];

    /**
     * Custom logic handler instance.
     */
    protected ?BaseCrudLogic $logicHandler = null;

    /**
     * Resource name from route parameter.
     */
    protected string $resource = '';

    /**
     * Resource configuration from crud.php.
     */
    protected array $config = [];

    /**
     * Initialize the controller based on the route resource parameter.
     *
     * @throws CrudException When resource configuration is not found
     */
    public function __construct()
    {
        // Initialize default values for typed properties
        $this->modelClass = '';
        $this->config = [];
        $this->validationRules = [];
        $this->relations = [];
        $this->logicHandler = null;
        $this->resource = '';

        // Skip initialization when running in console (e.g., artisan commands)
        if (app()->runningInConsole()) {
            return;
        }

        // Determine resource from route parameter or default
        $route = request()->route();
        $resource = '';
        if ($route) {
            $resource = (string) ($route->parameter('resource') ?? ($route->defaults['resource'] ?? '')); 
        }
        if (! $resource) {
            // No resource specified, skip initialization
            return;
        }
        $this->resource = $resource;

        // Load resource configuration
        $config = config("crud.resources.{$this->resource}");
        if (! is_array($config) || ! $config) {
            throw new CrudException("Resource '{$this->resource}' not configured in crud.php");
        }
        $this->config = $config;

        $this->initializeFromConfig();
    }

    /**
     * Initialize controller properties from configuration.
     */
    protected function initializeFromConfig(): void
    {
        $this->modelClass = $this->config['model'];
        // Support both 'validation' (legacy) and 'rules' (new structure)
        $this->validationRules = $this->config['validation'] ?? $this->config['rules'] ?? [];
        $this->relations = $this->config['relations'] ?? [];

        // Initialize custom logic handler if configured
        if (isset($this->config['logic']) && class_exists($this->config['logic'])) {
            $this->logicHandler = app($this->config['logic']);
        } else {
            $this->logicHandler = new BaseCrudLogic;
            $this->logicHandler->setModelClass($this->modelClass);
        }
    }

    /**
     * Display a paginated listing of the resource.
     *
     * Supports advanced filtering, searching, sorting, and pagination.
     * Query parameters:
     * - per_page: Number of items per page (default: 15, max: 100)
     * - page: Page number
     * - search: Global search term
     * - sort: Field to sort by
     * - direction: Sort direction (asc|desc)
     * - with_trashed: Include soft deleted records (requires permission)
     * - filter[field]: Filter by specific field values
     *
     * @throws UnauthorizedException
     */
    public function index(Request $request): JsonResponse
    {
        $this->checkPermission('view');

        try {
            $query = ($this->modelClass)::query();

            // Apply eager loading
            if ($this->relations) {
                $query->with($this->relations);
            }

            // Apply custom logic before building query
            $query = $this->logicHandler->beforeIndex($query, $request);

            // Handle soft deletes
            $this->applySoftDeleteHandling($query, $request);

            // Apply search
            $this->applySearch($query, $request);

            // Apply filters
            $this->applyFilters($query, $request);

            // Apply sorting
            $this->applySorting($query, $request);

            // Apply pagination
            $perPage = min((int) $request->get('per_page', $this->getDefaultPerPage()), $this->getMaxPerPage());
            $result = $query->paginate($perPage);

            // Transform results if needed
            $transformedData = $result->getCollection()->map(function ($item) use ($request) {
                return $this->logicHandler->transformResource($item, $request);
            });

            $result->setCollection($transformedData);

            return response()->json($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @throws UnauthorizedException
     */
    public function show(string $id): JsonResponse
    {
        $this->checkPermission('view');

        try {
            $query = ($this->modelClass)::query();

            if ($this->relations) {
                $query->with($this->relations);
            }

            // Handle soft deletes for show
            if (request()->has('with_trashed') && $this->checkPermission('view', false)) {
                $query->withTrashed();
            }

            $model = $query->findOrFail($id);

            // Transform the model data
            $transformedData = $this->logicHandler->transformResource($model, request());

            return response()->json($transformedData);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws UnauthorizedException
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->checkPermission('create');

        try {
            // Get validation rules (base + custom logic rules)
            $baseRules = $this->getValidationRulesForOperation('store');
            $customRules = $this->logicHandler->getValidationRules($request);
            $rules = array_merge($baseRules, $customRules);

            $messages = $this->logicHandler->getValidationMessages();

            $data = $request->validate($rules, $messages);

            // Apply custom logic before creating
            $data = $this->logicHandler->beforeCreate($data, $request);

            $model = ($this->modelClass)::create($data);

            // Load relationships for response
            if ($this->relations) {
                $model->load($this->relations);
            }

            // Apply custom logic after creating
            $this->logicHandler->afterCreate($model, $request);

            // Transform the model data
            $transformedData = $this->logicHandler->transformResource($model, $request);

            return response()->json($transformedData, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws UnauthorizedException
     * @throws ValidationException
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->checkPermission('edit');

        try {
            $model = ($this->modelClass)::findOrFail($id);

            // Get validation rules (base + custom logic rules)
            $baseRules = $this->getValidationRulesForOperation('update', $model);
            $customRules = $this->logicHandler->getValidationRules($request, $model);
            $rules = array_merge($baseRules, $customRules);

            $messages = $this->logicHandler->getValidationMessages();

            $data = $request->validate($rules, $messages);

            // Apply custom logic before updating
            $data = $this->logicHandler->beforeUpdate($data, $model, $request);

            $model->update($data);

            // Load relationships for response
            if ($this->relations) {
                $model->load($this->relations);
            }

            // Apply custom logic after updating
            $this->logicHandler->afterUpdate($model, $request);

            // Transform the model data
            $transformedData = $this->logicHandler->transformResource($model, $request);

            return response()->json($transformedData);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws UnauthorizedException
     */
    public function destroy(string $id): JsonResponse
    {
        $this->checkPermission('delete');

        try {
            $model = ($this->modelClass)::findOrFail($id);

            // Check if deletion is allowed
            if (! $this->logicHandler->beforeDelete($model, request())) {
                return response()->json(['error' => 'This resource cannot be deleted'], 400);
            }

            $model->delete();

            // Apply custom logic after deleting
            $this->logicHandler->afterDelete($model, request());

            return response()->json(null, 204);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Perform bulk operations on multiple resources.
     *
     * @throws UnauthorizedException
     */
    public function bulk(Request $request): JsonResponse
    {
        $operation = $request->input('operation');
        $ids = $request->input('ids', []);
        $data = $request->input('data', []);

        if (! in_array($operation, ['delete', 'restore', 'update', 'force_delete'])) {
            return response()->json(['error' => 'Invalid bulk operation'], 400);
        }

        $permission = match ($operation) {
            'delete', 'force_delete' => 'delete',
            'restore', 'update' => 'edit',
            default => 'edit'
        };

        $this->checkPermission($permission);

        try {
            $query = ($this->modelClass)::whereIn('id', $ids);

            if (in_array($operation, ['restore', 'force_delete'])) {
                $query->withTrashed();
            }

            $models = $query->get();
            $affected = 0;

            foreach ($models as $model) {
                switch ($operation) {
                    case 'delete':
                        if ($this->logicHandler->beforeDelete($model, $request)) {
                            $model->delete();
                            $this->logicHandler->afterDelete($model, $request);
                            $affected++;
                        }
                        break;
                    case 'restore':
                        $model->restore();
                        $affected++;
                        break;
                    case 'update':
                        if (! empty($data)) {
                            $processedData = $this->logicHandler->beforeUpdate($data, $model, $request);
                            $model->update($processedData);
                            $this->logicHandler->afterUpdate($model, $request);
                            $affected++;
                        }
                        break;
                    case 'force_delete':
                        if ($this->logicHandler->beforeDelete($model, $request)) {
                            $model->forceDelete();
                            $this->logicHandler->afterDelete($model, $request);
                            $affected++;
                        }
                        break;
                }
            }

            return response()->json([
                'message' => "Successfully processed {$affected} items",
                'operation' => $operation,
                'affected_count' => $affected,
                'requested_count' => count($ids),
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get trashed (soft deleted) resources.
     *
     * @throws UnauthorizedException
     */
    public function trashed(Request $request): JsonResponse
    {
        $this->checkPermission('view');

        if (! $this->supportsSoftDeletes()) {
            return response()->json(['error' => 'Soft deletes not supported for this resource'], 400);
        }

        try {
            $query = ($this->modelClass)::onlyTrashed();

            if ($this->relations) {
                $query->with($this->relations);
            }

            // Apply search and filters to trashed items
            $this->applySearch($query, $request);
            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            $perPage = min((int) $request->get('per_page', $this->getDefaultPerPage()), $this->getMaxPerPage());
            $result = $query->paginate($perPage);

            return response()->json($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Restore a soft deleted resource.
     *
     * @throws UnauthorizedException
     */
    public function restore(string $id): JsonResponse
    {
        $this->checkPermission('edit');

        if (! $this->supportsSoftDeletes()) {
            return response()->json(['error' => 'Soft deletes not supported for this resource'], 400);
        }

        try {
            $model = ($this->modelClass)::withTrashed()->findOrFail($id);
            $model->restore();

            return response()->json($model);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Force delete a resource (permanent deletion).
     *
     * @throws UnauthorizedException
     */
    public function forceDelete(string $id): JsonResponse
    {
        $this->checkPermission('delete');

        if (! $this->supportsSoftDeletes()) {
            return response()->json(['error' => 'Soft deletes not supported for this resource'], 400);
        }

        try {
            $model = ($this->modelClass)::withTrashed()->findOrFail($id);

            if (! $this->logicHandler->beforeDelete($model, request())) {
                return response()->json(['error' => 'This resource cannot be permanently deleted'], 400);
            }

            $model->forceDelete();
            $this->logicHandler->afterDelete($model, request());

            return response()->json(null, 204);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Generate API documentation for this resource.
     */
    public function documentation(): JsonResponse
    {
        $crudManager = app('crud-generator');
        $resourceDocs = $crudManager->generateResourceDocumentation($this->resource, $this->config);

        return response()->json($resourceDocs);
    }

    /**
     * Check if the current user has permission for the given action.
     *
     * @throws UnauthorizedException
     */
    protected function checkPermission(string $action, bool $throwException = true): bool
    {
        // Skip permission check if permissions are disabled for this resource
        if (! ($this->config['permissions']['enabled'] ?? false)) {
            return true;
        }

        $user = auth()->user();

        if (! $user) {
            if ($throwException) {
                throw UnauthorizedException::forPermissions(["{$action}-{$this->resource}"]);
            }

            return false;
        }

        $permissionFormat = $this->config['permissions']['format'] ?? config('crud.permissions.format', '{action}-{resource}');
        $permission = str_replace(['{action}', '{resource}'], [$action, $this->resource], $permissionFormat);

        $hasPermission = $user->can($permission);

        if (! $hasPermission && $throwException) {
            throw UnauthorizedException::forPermissions([$permission]);
        }

        return $hasPermission;
    }

    /**
     * Apply soft delete handling to the query.
     */
    protected function applySoftDeleteHandling(Builder $query, Request $request): void
    {
        if (! $this->supportsSoftDeletes()) {
            return;
        }

        if ($request->has('with_trashed') && $this->checkPermission('view', false)) {
            $query->withTrashed();
        } elseif ($request->has('only_trashed') && $this->checkPermission('view', false)) {
            $query->onlyTrashed();
        }
    }

    /**
     * Apply search functionality to the query.
     */
    protected function applySearch(Builder $query, Request $request): void
    {
        $search = $request->get('search');
        if (! $search) {
            return;
        }

        $searchConfig = $this->config['search'] ?? [];
        if (! ($searchConfig['enabled'] ?? false)) {
            return;
        }

        $searchableFields = $searchConfig['fields'] ?? ['name', 'title', 'description'];
        $operator = $searchConfig['operator'] ?? 'like';

        $query->where(function ($q) use ($searchableFields, $search, $operator) {
            foreach ($searchableFields as $field) {
                if ($operator === 'like') {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                } else {
                    $q->orWhere($field, $operator, $search);
                }
            }
        });
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query, Request $request): void
    {
        $filters = $request->get('filter', []);

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting(Builder $query, Request $request): void
    {
        $sortConfig = $this->config['sort'] ?? [];

        if (! ($sortConfig['enabled'] ?? true)) {
            return;
        }

        $sort = $request->get('sort', $sortConfig['default']['field'] ?? 'created_at');
        $direction = $request->get('direction', $sortConfig['default']['direction'] ?? 'desc');

        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $sortableFields = $sortConfig['fields'] ?? ['id', 'created_at', 'updated_at'];

        if (in_array($sort, $sortableFields)) {
            $query->orderBy($sort, $direction);
        } else {
            $defaultField = $sortConfig['default']['field'] ?? 'created_at';
            $defaultDirection = $sortConfig['default']['direction'] ?? 'desc';
            $query->orderBy($defaultField, $defaultDirection);
        }
    }

    /**
     * Check if this resource supports soft deletes.
     */
    protected function supportsSoftDeletes(): bool
    {
        return $this->config['soft_deletes'] ?? false;
    }

    /**
     * Get default items per page.
     */
    protected function getDefaultPerPage(): int
    {
        return $this->config['api']['per_page'] ?? config('crud.api.pagination.per_page', 15);
    }

    /**
     * Get maximum items per page.
     */
    protected function getMaxPerPage(): int
    {
        return $this->config['api']['max_per_page'] ?? config('crud.api.pagination.max_per_page', 100);
    }

    /**
     * Handle exceptions and return appropriate JSON response.
     */
    protected function handleException(Throwable $e): JsonResponse
    {
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        if ($e instanceof UnauthorizedException) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof CrudException) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Log the exception for debugging
        logger()->error('CrudController Exception', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'resource' => $this->resource,
            'model' => $this->modelClass,
        ]);

        return response()->json([
            'error' => 'An error occurred while processing your request',
            'message' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
        ], 500);
    }

    /**
     * Get validation rules for a specific operation.
     *
     * @param string $operation The operation type ('store' or 'update')
     * @param Model|null $model The model instance for update operations
     * @return array The validation rules
     */
    protected function getValidationRulesForOperation(string $operation, ?Model $model = null): array
    {
        // Support new 'rules' structure with store/update sections
        if (isset($this->validationRules[$operation])) {
            $rules = $this->validationRules[$operation];
            
            // Replace placeholders in validation rules
            if ($model && $operation === 'update') {
                $rules = $this->replacePlaceholdersInRules($rules, $model);
            }
            
            return $rules;
        }
        
        // Fallback to old 'validation' structure (legacy support)
        return $this->validationRules;
    }

    /**
     * Replace placeholders in validation rules.
     *
     * @param array $rules The validation rules
     * @param Model $model The model instance
     * @return array The rules with placeholders replaced
     */
    protected function replacePlaceholdersInRules(array $rules, Model $model): array
    {
        $replacements = [
            '{{id}}' => $model->getKey(),
            '{{user_id}}' => auth()->id() ?? 0,
            '{{resource}}' => $this->resource,
        ];

        $rulesJson = json_encode($rules);
        foreach ($replacements as $placeholder => $value) {
            $rulesJson = str_replace($placeholder, (string) $value, $rulesJson);
        }

        return json_decode($rulesJson, true);
    }
}

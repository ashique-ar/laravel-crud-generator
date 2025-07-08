<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the CRUD Generator.
 *
 * This facade provides convenient static access to the CrudManager instance,
 * allowing for easy route registration and resource management.
 *
 * @method static void registerRoutes(string $prefix = '', array $middleware = [])
 * @method static array getResources()
 * @method static array|null getResourceConfig(string $resource)
 * @method static bool hasResource(string $resource)
 * @method static string|null getResourceModel(string $resource)
 * @method static string|null getResourceLogic(string $resource)
 * @method static array generateApiDocumentation()
 *
 * @see \AshiqueAr\LaravelCrudGenerator\Services\CrudManager
 */
class CrudGenerator extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'crud-generator';
    }
}

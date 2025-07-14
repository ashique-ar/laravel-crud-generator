<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check CRUD permissions for API endpoints.
 *
 * This middleware automatically determines the required permission
 * based on the HTTP method and route pattern, then checks if the
 * authenticated user has the necessary permission.
 */
class CheckCrudPermission
{
    /**
     * Handle an incoming request.
     *
     * @throws UnauthorizedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            throw UnauthorizedException::notLoggedIn();
        }

        // Get resource and action from route
        $resource = $this->getResourceFromRoute($request);
        $action = $this->getActionFromRequest($request);

        if (!$resource || !$action) {
            return $next($request);
        }

        // Check if permissions are enabled for this resource
        $resourceConfig = config("crud.resources.{$resource}");
        // if (! ($resourceConfig['permissions']['enabled'] ?? false)) {
        //     return $next($request);
        // }
        $enabled = $resourceConfig['permissions']['enabled']
            ?? config('crud.permissions.enabled', false);

        if (!$enabled) {
            return $next($request);
        }
        
        // Check if user has super admin role
        $superAdminRole = config('crud.permissions.super_admin_role', 'super-admin');
        if ($user->hasRole($superAdminRole)) {
            return $next($request);
        }

        // Build permission name
        $format = $resourceConfig['permissions']['format'] ?? config('crud.permissions.format', '{action}-{resource}');
        $permission = str_replace(
            ['{action}', '{resource}'],
            [$action, $resource],
            $format
        );

        // Check permission
        if (!$user->can($permission)) {
            throw UnauthorizedException::forPermissions([$permission]);
        }

        return $next($request);
    }

    /**
     * Get the resource name from the route.
     */
    protected function getResourceFromRoute(Request $request): ?string
    {
        $route = $request->route();

        if (!$route) {
            return null;
        }

        // Try to get resource from route parameter
        $resource = $route->parameter('resource');

        if ($resource) {
            return $resource;
        }

        // Try to extract resource from route name
        $routeName = $route->getName();
        if ($routeName && str_contains($routeName, 'crud.')) {
            $parts = explode('.', $routeName);
            if (count($parts) >= 2) {
                return $parts[1];
            }
        }

        // Try to extract from URI pattern
        $uri = $route->uri();
        if (preg_match('/^(?:api\/)?(?:v\d+\/)?([^\/]+)(?:\/.*)?$/', $uri, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Determine the action based on HTTP method and route pattern.
     */
    protected function getActionFromRequest(Request $request): ?string
    {
        $method = strtoupper($request->method());
        $route = $request->route();
        $uri = $route ? $route->uri() : '';
        $routeName = $route ? $route->getName() : '';

        // Check route name first (most reliable)
        if ($routeName && str_contains($routeName, 'crud.')) {
            $parts = explode('.', $routeName);
            $action = end($parts);

            return match ($action) {
                'index' => 'view',
                'show' => 'view',
                'store' => 'create',
                'update', 'patch' => 'edit',
                'destroy' => 'delete',
                'bulk' => $this->getBulkAction($request),
                'trashed' => 'view',
                'restore' => 'edit',
                'force-delete' => 'force-delete',
                'docs', 'documentation' => 'view',
                default => null,
            };
        }

        // Fallback to HTTP method and URI pattern
        return match ($method) {
            'GET' => $this->getGetAction($uri),
            'POST' => $this->getPostAction($uri, $request),
            'PUT', 'PATCH' => $this->getPutPatchAction($uri),
            'DELETE' => $this->getDeleteAction($uri),
            default => null,
        };
    }

    /**
     * Determine GET action based on URI pattern.
     */
    protected function getGetAction(string $uri): string
    {
        if (str_contains($uri, '/trashed')) {
            return 'view';
        }

        if (str_contains($uri, '/docs')) {
            return 'view';
        }

        // Both index and show use 'view' permission
        return 'view';
    }

    /**
     * Determine POST action based on URI pattern and request data.
     */
    protected function getPostAction(string $uri, Request $request): string
    {
        if (str_contains($uri, '/bulk')) {
            return $this->getBulkAction($request);
        }

        if (str_contains($uri, '/restore')) {
            return 'edit';
        }

        return 'create';
    }

    /**
     * Determine PUT/PATCH action based on URI pattern.
     */
    protected function getPutPatchAction(string $uri): string
    {
        return 'edit';
    }

    /**
     * Determine DELETE action based on URI pattern.
     */
    protected function getDeleteAction(string $uri): string
    {
        if (str_contains($uri, '/force')) {
            return 'force-delete';
        }

        return 'delete';
    }

    /**
     * Determine the action for bulk operations.
     */
    protected function getBulkAction(Request $request): string
    {
        $operation = $request->input('operation');

        return match ($operation) {
            'delete', 'force_delete' => 'delete',
            'restore', 'update' => 'edit',
            default => 'edit',
        };
    }

    /**
     * Check if the route is for showing a specific resource.
     */
    protected function isShowRoute(string $uri): bool
    {
        // Check if URI ends with an ID parameter pattern
        return preg_match('/\/\{[^}]+\}$/', $uri) ||
            preg_match('/\/[0-9]+$/', $uri);
    }

    /**
     * Check if the route is an index route.
     */
    protected function isIndexRoute(string $uri): bool
    {
        // Remove query parameters and check if it's a clean resource URI
        $cleanUri = explode('?', $uri)[0];

        return !$this->isShowRoute($cleanUri) &&
            !str_contains($cleanUri, '/bulk') &&
            !str_contains($cleanUri, '/trashed') &&
            !str_contains($cleanUri, '/docs');
    }
}

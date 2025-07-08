<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Exceptions;

use Exception;

/**
 * Permission denied exception for CRUD operations.
 *
 * This exception is thrown when a user attempts to perform
 * a CRUD operation without the required permissions.
 */
class PermissionDeniedException extends Exception
{
    /**
     * The required permission(s).
     */
    protected array $permissions;

    /**
     * Create a new permission denied exception instance.
     *
     * @param  array|string  $permissions
     */
    public function __construct($permissions = [], string $message = 'Permission denied', int $code = 403, ?Exception $previous = null)
    {
        $this->permissions = is_array($permissions) ? $permissions : [$permissions];

        if (empty($message) || $message === 'Permission denied') {
            $message = $this->generateMessage();
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the required permissions.
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Generate a descriptive error message.
     */
    protected function generateMessage(): string
    {
        if (empty($this->permissions)) {
            return 'You do not have permission to perform this action.';
        }

        if (count($this->permissions) === 1) {
            return "You do not have the required permission: {$this->permissions[0]}";
        }

        $permissionList = implode(', ', $this->permissions);

        return "You do not have one of the required permissions: {$permissionList}";
    }

    /**
     * Create exception for missing CRUD permission.
     */
    public static function forCrudAction(string $action, string $resource): static
    {
        $permission = "{$action}-{$resource}";

        return new static([$permission], "You do not have permission to {$action} {$resource} resources.");
    }

    /**
     * Create exception for multiple missing permissions.
     */
    public static function forPermissions(array $permissions): static
    {
        return new static($permissions);
    }
}

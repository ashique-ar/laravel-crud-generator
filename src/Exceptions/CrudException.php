<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Exceptions;

use Exception;

/**
 * General CRUD operation exception.
 * 
 * This exception is thrown when CRUD operations encounter errors
 * such as invalid configurations, missing resources, or business logic violations.
 */
class CrudException extends Exception
{
    /**
     * Create a new CRUD exception instance.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = 'CRUD operation failed', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for invalid resource configuration.
     */
    public static function invalidResource(string $resource): static
    {
        return new static("Resource '{$resource}' is not properly configured in crud.php");
    }

    /**
     * Create exception for missing model class.
     */
    public static function missingModel(string $modelClass): static
    {
        return new static("Model class '{$modelClass}' does not exist");
    }

    /**
     * Create exception for invalid operation.
     */
    public static function invalidOperation(string $operation): static
    {
        return new static("Operation '{$operation}' is not supported");
    }

    /**
     * Create exception for validation failures.
     */
    public static function validationFailed(string $message = 'Validation failed'): static
    {
        return new static($message);
    }
}



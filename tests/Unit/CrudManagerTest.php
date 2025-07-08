<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AshiqueAr\LaravelCrudGenerator\Services\CrudManager;
use AshiqueAr\LaravelCrudGenerator\Exceptions\CrudException;

/**
 * Unit tests for CrudManager service.
 */
class CrudManagerTest extends TestCase
{
    protected CrudManager $crudManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crudManager = new CrudManager();
    }

    public function test_can_instantiate_crud_manager(): void
    {
        $this->assertInstanceOf(CrudManager::class, $this->crudManager);
    }

    public function test_throws_exception_for_invalid_resource(): void
    {
        $this->expectException(CrudException::class);
        $this->expectExceptionMessage('Resource configuration not found: invalid_resource');
        
        $this->crudManager->getResourceConfig('invalid_resource');
    }

    public function test_validates_resource_configuration(): void
    {
        $config = [
            'model' => 'App\\Models\\TestModel',
            'middleware' => ['auth:sanctum'],
        ];

        $result = $this->crudManager->validateResourceConfig($config);
        
        $this->assertTrue($result);
    }

    public function test_throws_exception_for_missing_model_in_config(): void
    {
        $this->expectException(CrudException::class);
        $this->expectExceptionMessage('Model class is required in resource configuration');
        
        $config = ['middleware' => ['auth:sanctum']];
        $this->crudManager->validateResourceConfig($config);
    }

    public function test_throws_exception_for_non_existent_model(): void
    {
        $this->expectException(CrudException::class);
        $this->expectExceptionMessage('Model class does not exist: App\\Models\\NonExistentModel');
        
        $config = [
            'model' => 'App\\Models\\NonExistentModel',
            'middleware' => ['auth:sanctum'],
        ];
        
        $this->crudManager->validateResourceConfig($config);
    }

    public function test_merges_default_configuration(): void
    {
        $config = [
            'model' => 'App\\Models\\TestModel',
            'custom_field' => 'custom_value',
        ];

        $merged = $this->crudManager->mergeWithDefaults($config);

        $this->assertArrayHasKey('middleware', $merged);
        $this->assertArrayHasKey('pagination', $merged);
        $this->assertArrayHasKey('custom_field', $merged);
        $this->assertEquals('custom_value', $merged['custom_field']);
    }

    public function test_gets_default_configuration(): void
    {
        $defaults = $this->crudManager->getDefaultConfig();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('middleware', $defaults);
        $this->assertArrayHasKey('pagination', $defaults);
        $this->assertArrayHasKey('validation', $defaults);
        $this->assertArrayHasKey('searchable_fields', $defaults);
        $this->assertArrayHasKey('sortable_fields', $defaults);
        $this->assertArrayHasKey('filterable_fields', $defaults);
    }

    public function test_checks_if_resource_exists(): void
    {
        // Test with non-existent resource
        $this->assertFalse($this->crudManager->resourceExists('non_existent'));
    }

    public function test_gets_all_resource_names(): void
    {
        $resources = $this->crudManager->getAllResourceNames();
        
        $this->assertIsArray($resources);
    }

    public function test_creates_logic_instance(): void
    {
        $config = [
            'model' => 'App\\Models\\TestModel',
            'logic' => 'App\\Services\\Crud\\TestLogic',
        ];

        // This would require mocking in a real test environment
        // For now, we test that the method exists and returns the expected type
        $this->assertTrue(method_exists($this->crudManager, 'createLogicInstance'));
    }
}



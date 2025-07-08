<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Request;
use AshiqueAr\LaravelCrudGenerator\Services\Crud\BaseCrudLogic;
use AshiqueAr\LaravelCrudGenerator\Exceptions\CrudException;

/**
 * Unit tests for BaseCrudLogic service.
 */
class BaseCrudLogicTest extends TestCase
{
    protected BaseCrudLogic $logic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logic = new BaseCrudLogic();
        $this->logic->setModelClass('TestModel');
    }

    public function test_can_instantiate_base_crud_logic(): void
    {
        $this->assertInstanceOf(BaseCrudLogic::class, $this->logic);
    }

    public function test_has_required_abstract_method(): void
    {
        $reflection = new \ReflectionClass(BaseCrudLogic::class);
        $this->assertTrue($reflection->hasMethod('getModelClass'));
        $this->assertFalse($reflection->getMethod('getModelClass')->isAbstract());
    }

    public function test_has_validation_methods(): void
    {
        $this->assertTrue(method_exists($this->logic, 'getValidationRules'));
        $this->assertTrue(method_exists($this->logic, 'getValidationMessages'));
    }

    public function test_has_hook_methods(): void
    {
        $hookMethods = [
            'beforeCreate',
            'afterCreate',
            'beforeUpdate',
            'afterUpdate',
            'beforeDelete',
            'afterDelete',
        ];

        foreach ($hookMethods as $method) {
            $this->assertTrue(method_exists($this->logic, $method), "Method {$method} should exist");
        }
    }

    public function test_has_authorization_method(): void
    {
        $this->assertTrue(method_exists($this->logic, 'authorize'));
    }

    public function test_has_query_customization_method(): void
    {
        $this->assertTrue(method_exists($this->logic, 'beforeIndex'));
    }

    public function test_can_set_and_get_model_class(): void
    {
        $this->logic->setModelClass('App\\Models\\User');
        $this->assertEquals('App\\Models\\User', $this->logic->getModelClass());
    }

    public function test_default_validation_rules_return_empty_array(): void
    {
        $request = $this->createMock(\Illuminate\Http\Request::class);
        $rules = $this->logic->getValidationRules($request);
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }

    public function test_default_validation_messages_return_empty_array(): void
    {
        $messages = $this->logic->getValidationMessages();
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }
}



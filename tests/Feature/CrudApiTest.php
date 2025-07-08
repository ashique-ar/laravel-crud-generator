<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Tests\Feature;

use AshiqueAr\LaravelCrudGenerator\CrudGeneratorServiceProvider;
use AshiqueAr\LaravelCrudGenerator\Tests\Models\TestUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase;

/**
 * Feature tests for CRUD API endpoints.
 */
class CrudApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            CrudGeneratorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure CRUD resources for testing
        $app['config']->set('crud.resources.test_users', [
            'model' => TestUser::class,
            'middleware' => [],
            'validation' => [
                'store' => [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:test_users',
                ],
                'update' => [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email',
                ],
            ],
            'pagination' => [
                'per_page' => 15,
                'max_per_page' => 100,
            ],
            'searchable_fields' => ['name', 'email'],
            'sortable_fields' => ['id', 'name', 'email', 'created_at'],
            'filterable_fields' => ['name', 'email'],
            'soft_deletes' => false,
        ]);
    }

    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function test_can_list_resources(): void
    {
        // Create test data
        TestUser::factory()->count(5)->create();

        $response = $this->getJson('/api/crud/test_users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_can_create_resource(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];

        $response = $this->postJson('/api/crud/test_users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ]);

        $this->assertDatabaseHas('test_users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_validates_required_fields_on_create(): void
    {
        $response = $this->postJson('/api/crud/test_users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_can_show_single_resource(): void
    {
        $user = TestUser::factory()->create();

        $response = $this->getJson("/api/crud/test_users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_returns_404_for_non_existent_resource(): void
    {
        $response = $this->getJson('/api/crud/test_users/999');

        $response->assertStatus(404);
    }

    public function test_can_update_resource(): void
    {
        $user = TestUser::factory()->create();
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/crud/test_users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ]);

        $this->assertDatabaseHas('test_users', [
            'id' => $user->id,
            'name' => $updateData['name'],
            'email' => $updateData['email'],
        ]);
    }

    public function test_can_delete_resource(): void
    {
        $user = TestUser::factory()->create();

        $response = $this->deleteJson("/api/crud/test_users/{$user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('test_users', [
            'id' => $user->id,
        ]);
    }

    public function test_can_search_resources(): void
    {
        TestUser::factory()->create(['name' => 'John Doe']);
        TestUser::factory()->create(['name' => 'Jane Smith']);
        TestUser::factory()->create(['name' => 'Bob Johnson']);

        $response = $this->getJson('/api/crud/test_users?search=John');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('John Doe', $names);
        $this->assertContains('Bob Johnson', $names);
        $this->assertNotContains('Jane Smith', $names);
    }

    public function test_can_sort_resources(): void
    {
        TestUser::factory()->create(['name' => 'Charlie']);
        TestUser::factory()->create(['name' => 'Alice']);
        TestUser::factory()->create(['name' => 'Bob']);

        $response = $this->getJson('/api/crud/test_users?sort=name&direction=asc');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $names);
    }

    public function test_can_filter_resources(): void
    {
        TestUser::factory()->create(['name' => 'John Doe']);
        TestUser::factory()->create(['name' => 'Jane Doe']);
        TestUser::factory()->create(['name' => 'Bob Smith']);

        $response = $this->getJson('/api/crud/test_users?filter[name]=Doe');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('John Doe', $names);
        $this->assertContains('Jane Doe', $names);
        $this->assertNotContains('Bob Smith', $names);
    }

    public function test_pagination_works_correctly(): void
    {
        TestUser::factory()->count(25)->create();

        $response = $this->getJson('/api/crud/test_users?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'current_page' => 2,
                    'per_page' => 10,
                    'total' => 25,
                ],
            ]);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_respects_max_per_page_limit(): void
    {
        TestUser::factory()->count(150)->create();

        $response = $this->getJson('/api/crud/test_users?per_page=200');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'per_page' => 100, // Should be limited to max_per_page
                ],
            ]);
    }
}

<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use AshiqueAr\LaravelCrudGenerator\Tests\Models\TestUser;

/**
 * Factory for TestUser model.
 */
class TestUserFactory extends Factory
{
    protected $model = TestUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}



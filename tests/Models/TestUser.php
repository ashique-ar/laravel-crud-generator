<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use AshiqueAr\LaravelCrudGenerator\Tests\Factories\TestUserFactory;

/**
 * Test model for CRUD functionality testing.
 */
class TestUser extends Model
{
    use HasFactory;

    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return TestUserFactory::new();
    }
}



<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function seedAccess(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function makeUser(string $role, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'locale' => 'ar',
            'is_active' => true,
        ], $overrides));
        $user->assignRole($role);

        return $user;
    }
}

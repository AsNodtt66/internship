<?php

namespace Tests\Feature\Safety;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class RoleInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_role_slug_cannot_be_changed(): void
    {
        $this->seed(RoleSeeder::class);
        $role = Role::where('slug', 'pic')->firstOrFail();

        $this->expectException(LogicException::class);
        $role->update(['slug' => 'pic_changed']);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $this->seed(RoleSeeder::class);
        $role = Role::where('slug', 'pic')->firstOrFail();

        $this->expectException(LogicException::class);
        $role->delete();
    }
}

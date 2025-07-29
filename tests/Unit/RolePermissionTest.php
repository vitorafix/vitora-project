<?php

namespace Tests\Unit;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase; // برای ریست دیتابیس بعد از هر تست

    protected function setUp(): void
    {
        parent::setUp();

        // ایجاد نقش‌ها و مجوزها
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'editor']);
        Permission::create(['name' => 'create post']);
        Permission::create(['name' => 'edit post']);
        Permission::create(['name' => 'delete post']);
    }

    /** @test */
    public function a_user_can_be_assigned_a_role()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
    }

    /** @test */
    public function a_role_can_be_given_a_permission()
    {
        $role = Role::findByName('admin');
        $permission = Permission::findByName('create post');

        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermissionTo('create post'));
    }

    /** @test */
    public function a_user_with_a_role_has_the_roles_permissions()
    {
        $user = User::factory()->create();
        $role = Role::findByName('admin');
        $permission = Permission::findByName('edit post');

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermissionTo('edit post'));
    }

    /** @test */
    public function a_user_can_have_direct_permissions()
    {
        $user = User::factory()->create();
        $permission = Permission::findByName('delete post');

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionTo('delete post'));
    }

    /** @test */
    public function admin_role_can_do_anything_via_gate_before_hook()
    {
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        $this->assertTrue($adminUser->can('any-permission-that-does-not-exist'));
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $arrayOfRoleNames = [
            ['name' => 'super-admin', 'label' => 'Super Admin'],
            ['name' => 'admin', 'label' => 'Admin'],
            ['name' => 'editor', 'label' => 'Editor'],
            ['name' => 'subscriber', 'label' => 'Subscriber'],
            ['name' => 'premium-plus', 'label' => 'Premium Plus'],
            ['name' => 'group-moderator', 'label' => 'Group Moderator'],
            ['name' => 'subgroup-moderator', 'label' => 'Sub Group Moderator'],
        ];

        $roles = collect($arrayOfRoleNames)->map(function ($role) {
            return [
                'name' => $role['name'],
                'label' => $role['label'],
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Role::query()->insert($roles->toArray());


        $arrayOfPermissionNames = [
            "viewAny user", "view user", "create user", "update user", "delete user",
            "viewAny role", "view role", "create role", "update role", "delete role",
            "viewAny permission", "view permission", "update permission",
            "viewAny activity", "view activity", "delete activity",
        ];

        $permissions = collect($arrayOfPermissionNames)->map(function ($permission) {
            return [
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Permission::query()->insert($permissions->toArray());
        $role = Role::where(['name' => 'super-admin'])->first();
        $role->givePermissionTo(Permission::all());
    }
}

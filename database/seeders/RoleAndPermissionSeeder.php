<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'catalog.view',
            'catalog.view_internal_notes',
            'editions.create',
            'editions.update',
            'editions.delete',
            'copies.create',
            'copies.update',
            'copies.delete',
            'copies.move',
            'copies.transition',
            'loans.create',
            'loans.return',
            'loans.renew',
            'loans.view_any',
            'loans.view_own',
            'users.manage',
            'users.verify_identity',
            'roles.manage',
            'settings.manage',
            'policies.manage',
            'taxonomy.manage',
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'administrator' => $permissions,
            'librarian' => [
                'catalog.view',
                'catalog.view_internal_notes',
                'editions.create',
                'editions.update',
                'copies.create',
                'copies.update',
                'copies.move',
                'copies.transition',
                'loans.create',
                'loans.return',
                'loans.renew',
                'loans.view_any',
                'loans.view_own',
                'users.verify_identity',
                'reports.view',
            ],
            'shelver' => [
                'catalog.view',
                'copies.move',
                'copies.transition',
                'loans.view_own',
            ],
            'reader' => [
                'catalog.view',
                'loans.view_own',
            ],
        ];

        foreach ($roles as $name => $rolePermissions) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($rolePermissions);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CoreRbacSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::query()->firstOrCreate(['name' => 'super_admin']);
        $manager = Role::query()->firstOrCreate(['name' => 'store_manager']);
        $cashier = Role::query()->firstOrCreate(['name' => 'cashier']);

        $permissions = [
            'products.read', 'products.write',
            'inventory.read', 'inventory.write',
            'sales.read', 'sales.write',
            'stores.read', 'stores.write',
            'franchises.read', 'franchises.write',
            'settings.manage', 'rbac.manage'
        ];

        $permissionIds = collect($permissions)
            ->map(fn ($name) => Permission::query()->firstOrCreate(['name' => $name])->id)
            ->all();

        $admin->permissions()->sync($permissionIds);
        $manager->permissions()->sync(Permission::query()->whereIn('name', [
            'products.read', 'products.write', 'inventory.read', 'inventory.write', 'sales.read', 'sales.write', 'stores.read'
        ])->pluck('id')->all());
        $cashier->permissions()->sync(Permission::query()->whereIn('name', ['sales.read', 'sales.write'])->pluck('id')->all());
    }
}

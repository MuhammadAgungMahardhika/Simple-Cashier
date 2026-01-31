<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionAndRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Menyimpan permissions ke dalam variabel
        $userPermissions = [
            'view-any-user',
            'view-user',
            'create-user',
            'update-user',
            'delete-user',
        ];
        $rolePermissions = [
            'view-any-role',
            'view-role',
            'create-role',
            'update-role',
            'delete-role',
        ];

        $customerPermissions = [
            'view-any-customer',
            'view-customer',
            'create-customer',
            'update-customer',
            'delete-customer',
        ];

        $servicePermissions = [
            'view-any-service',
            'view-service',
            'create-service',
            'update-service',
            'delete-service',
        ];

        $transactionPermissions = [
            'view-any-transaction',
            'view-transaction',
            'create-transaction',
            'update-transaction',
            'delete-transaction',
        ];

        $discountPermissions = [
            'view-any-discount',
            'view-discount',
            'create-discount',
            'update-discount',
            'delete-discount',
        ];


        $widgetPermissions = [
            "view-monthly-revenue-widget",
            "view-payment-method-widget",
            "view-popular-services-widget",
            "view-recent-transactions-widget",
            "view-revenue-widget",
            "view-stats-widget",
            "view-top-customers-widget",
        ];
        // Menggabungkan semua permissions ke dalam satu array untuk proses pembuatan permissions
        $allPermissions = array_merge(
            $userPermissions,
            $rolePermissions,
            $customerPermissions,
            $servicePermissions,
            $transactionPermissions,
            $discountPermissions,
            $widgetPermissions,
        );

        // Membuat semua permissions
        foreach ($allPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        // Menyimpan roles ke dalam variabel
        $roles = [
            'superAdmin' => Permission::all(),
            'admin' => array_merge(
                $userPermissions,
                $customerPermissions,
                $servicePermissions,
                $transactionPermissions,
                $discountPermissions,
                $widgetPermissions,
            ),
            'cashier' => [
                'view-any-customer',
                'view-customer',
                'create-customer',
                'update-customer',
                'delete-customer',

                'view-any-service',
                'view-service',

                'view-any-transaction',
                'view-transaction',
                'create-transaction',
                'update-transaction',

                'view-monthly-revenue-widget',
                'view-payment-method-widget',
                'view-popular-services-widget',
                'view-recent-transactions-widget',
                'view-revenue-widget',
                'view-stats-widget',
                'view-top-customers-widget',
            ],

        ];

        // Membuat roles dan memberikan permissions
        foreach ($roles as $roleName => $permissions) {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($permissions);
        }

        $superAdminUser =   User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('nj1nkl0e')
        ]);
        $adminUser =   User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password')
        ]);
        $caUser =   User::factory()->create([
            'name' => 'Cashier',
            'email' => 'cashier@example.com',
            'password' => Hash::make('password')
        ]);

        // asign role
        $superAdminUser->assignRole('superAdmin');
        $adminUser->assignRole('admin');
        $caUser->assignRole('cashier');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing warehouses
        $tphcmWarehouse = Warehouse::where('name', 'Kho TP.HCM')->first();
        $hanoiWarehouse = Warehouse::where('name', 'Kho Ha Noi')->first();

        // Get existing roles
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $endUserRole = Role::where('name', 'endUser')->first();

        // If required warehouses or roles don't exist, exit
        if (!$tphcmWarehouse || !$hanoiWarehouse || !$adminRole || !$managerRole || !$endUserRole) {
            echo "Required warehouses or roles not found. Please run WarehouseSeeder and RoleSeeder first.\n";
            return;
        }

        // Create specific users with assigned roles and warehouses
        // Admin users
        User::firstOrCreate(
            ['email' => 'admin@perw.com'],
            [
                'name' => 'Admin User',
                'full_name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'status' => 'active',
                'phone_number' => '0123456789'
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin2@perw.com'],
            [
                'name' => 'Admin User 2',
                'full_name' => 'Admin User 2',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'status' => 'active',
                'phone_number' => '0987654321'
            ]
        );

        // Manager for TP.HCM warehouse
        User::firstOrCreate(
            ['email' => 'manager.tphcm@perw.com'],
            [
                'name' => 'TP.HCM Manager',
                'full_name' => 'TP.HCM Manager',
                'password' => Hash::make('password'),
                'role_id' => $managerRole->id,
                'warehouse_id' => $tphcmWarehouse->id,
                'status' => 'active',
                'phone_number' => '0111111111'
            ]
        );

        // Manager for Ha Noi warehouse
        User::firstOrCreate(
            ['email' => 'manager.hanoi@perw.com'],
            [
                'name' => 'Ha Noi Manager',
                'full_name' => 'Ha Noi Manager',
                'password' => Hash::make('password'),
                'role_id' => $managerRole->id,
                'warehouse_id' => $hanoiWarehouse->id,
                'status' => 'active',
                'phone_number' => '0222222222'
            ]
        );

        // End users
        User::firstOrCreate(
            ['email' => 'enduser1@perw.com'],
            [
                'name' => 'End User 1',
                'full_name' => 'End User 1',
                'password' => Hash::make('password'),
                'role_id' => $endUserRole->id,
                'status' => 'active',
                'phone_number' => '0333333333'
            ]
        );

        User::firstOrCreate(
            ['email' => 'enduser2@perw.com'],
            [
                'name' => 'End User 2',
                'full_name' => 'End User 2',
                'password' => Hash::make('password'),
                'role_id' => $endUserRole->id,
                'status' => 'active',
                'phone_number' => '0444444444'
            ]
        );

        $targetEndUsers = 50;
        $existingEndUsers = User::where('role_id', $endUserRole->id)->count();

        if ($existingEndUsers < $targetEndUsers) {
            $additionalEndUsers = $targetEndUsers - $existingEndUsers;

            User::factory()
                ->count($additionalEndUsers)
                ->state(fn () => [
                    'role_id' => $endUserRole->id,
                    'warehouse_id' => null,
                ])
                ->create();
        }

        echo "Users created successfully.\n";
    }
}

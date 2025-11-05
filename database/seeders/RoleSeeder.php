<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Administrator with full access',
            ],
            [
                'name' => 'manager',
                'description' => 'Inventory manager with limited access',
            ],
            [
                'name' => 'endUser',
                'description' => 'End user customer',
            ],
        ];

        // Remove any roles not in the approved list
        Role::query()
            ->whereNotIn('name', array_column($roles, 'name'))
            ->delete();

        // Ensure the expected roles exist with the correct descriptions
        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}

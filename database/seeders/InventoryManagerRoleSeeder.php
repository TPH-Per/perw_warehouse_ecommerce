<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class InventoryManagerRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Inventory Manager role
        Role::create([
            'name' => 'Inventory Manager',
            'description' => 'Manages inventory and can sell products directly to customers without shipping. Cannot add/delete products.',
        ]);
    }
}

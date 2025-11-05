<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Kho TP.HCM',
                'location' => 'TPHCM',
            ],
            [
                'name' => 'Kho Ha Noi',
                'location' => 'Ha Noi',
            ],
        ];

        // Remove warehouses outside of the approved list
        Warehouse::query()
            ->whereNotIn('name', array_column($warehouses, 'name'))
            ->delete();

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(['name' => $warehouse['name']], $warehouse);
        }
    }
}

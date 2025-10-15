<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Role;
use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BasicPerWSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        Role::create([
            'name' => 'Admin',
            'description' => 'Quản trị viên hệ thống, có toàn quyền.'
        ]);

        Role::create([
            'name' => 'End User',
            'description' => 'Khách hàng mua sắm trên trang web.'
        ]);

        // Create categories
        $figure = Category::create([
            'name' => 'Figure',
            'slug' => 'figure'
        ]);

        Category::create([
            'parent_id' => $figure->id,
            'name' => 'Nendoroid',
            'slug' => 'nendoroid'
        ]);

        Category::create([
            'parent_id' => $figure->id,
            'name' => 'Scale Figure',
            'slug' => 'scale-figure'
        ]);

        $merchandise = Category::create([
            'name' => 'Merchandise',
            'slug' => 'merchandise'
        ]);

        Category::create([
            'parent_id' => $merchandise->id,
            'name' => 'Apparel',
            'slug' => 'apparel'
        ]);

        Category::create([
            'parent_id' => $merchandise->id,
            'name' => 'Keychains',
            'slug' => 'keychains'
        ]);

        // Create suppliers
        Supplier::create([
            'name' => 'Good Smile Company',
            'contact_info' => 'Nhà sản xuất figure hàng đầu Nhật Bản.'
        ]);

        Supplier::create([
            'name' => 'Kotobukiya',
            'contact_info' => 'Chuyên về scale figure và model kits.'
        ]);

        Supplier::create([
            'name' => 'Bandai',
            'contact_info' => 'Tập đoàn đồ chơi và giải trí lớn.'
        ]);

        Supplier::create([
            'name' => 'Uniqlo',
            'contact_info' => 'Đối tác hợp tác sản xuất áo thun UT.'
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerWDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::factory()->create([
            'name' => 'Admin',
            'description' => 'Quản trị viên hệ thống, có toàn quyền.'
        ]);

        $managerRole = Role::factory()->create([
            'name' => 'Inventory Manager',
            'description' => 'Quản lý kho và hàng tồn kho.'
        ]);

        $userRole = Role::factory()->create([
            'name' => 'End User',
            'description' => 'Khách hàng mua sắm trên trang web.'
        ]);

        // Create categories
        $figureCategory = Category::factory()->create([
            'name' => 'Figure',
            'slug' => 'figure'
        ]);

        $nendoroidCategory = Category::factory()->create([
            'parent_id' => $figureCategory->id,
            'name' => 'Nendoroid',
            'slug' => 'nendoroid'
        ]);

        $scaleFigureCategory = Category::factory()->create([
            'parent_id' => $figureCategory->id,
            'name' => 'Scale Figure',
            'slug' => 'scale-figure'
        ]);

        $merchandiseCategory = Category::factory()->create([
            'name' => 'Merchandise',
            'slug' => 'merchandise'
        ]);

        $apparelCategory = Category::factory()->create([
            'parent_id' => $merchandiseCategory->id,
            'name' => 'Apparel',
            'slug' => 'apparel'
        ]);

        // Ensure payment methods (including Online and VNPAY) exist
        PaymentMethod::firstOrCreate(
            ['code' => 'online'],
            ['name' => 'Thanh toán trực tuyến', 'is_active' => true]
        );
        PaymentMethod::firstOrCreate(
            ['code' => 'vnpay'],
            ['name' => 'VNPAY', 'is_active' => true]
        );
        // Checkout.vn payment method (Removed)

        PaymentMethod::firstOrCreate(
            ['code' => 'cod'],
            ['name' => 'Thanh toán khi nhận hàng', 'is_active' => true]
        );

        // Create suppliers
        $goodSmile = Supplier::factory()->create([
            'name' => 'Good Smile Company',
            'contact_info' => 'Nhà sản xuất figure hàng đầu Nhật Bản.'
        ]);

        $kotobukiya = Supplier::factory()->create([
            'name' => 'Kotobukiya',
            'contact_info' => 'Chuyên về scale figure và model kits.'
        ]);

        $uniqlo = Supplier::factory()->create([
            'name' => 'Uniqlo',
            'contact_info' => 'Đối tác hợp tác sản xuất áo thun UT.'
        ]);

        // Create warehouses
        $hanoiWarehouse = Warehouse::factory()->create([
            'name' => 'Kho Hà Nội',
            'location' => 'Khu công nghiệp Thăng Long, Hà Nội'
        ]);

        $hcmWarehouse = Warehouse::factory()->create([
            'name' => 'Kho TP. Hồ Chí Minh',
            'location' => 'Khu công nghiệp Tân Bình, TP. HCM'
        ]);

        // Create admin user
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'full_name' => 'PerW Admin',
            'email' => 'admin@perw.com',
            'password' => bcrypt('password'),
            'phone_number' => '0900000001',
            'status' => 'active'
        ]);

        // Create warehouse-specific managers
        $phucHung = User::factory()->create([
            'role_id' => $managerRole->id,
            'warehouse_id' => $hcmWarehouse->id,
            'full_name' => 'Phúc Hưng',
            'email' => 'phuc.hung@perw.com',
            'password' => bcrypt('password'),
            'phone_number' => '0900000003',
            'status' => 'active'
        ]);

        $tung = User::factory()->create([
            'role_id' => $managerRole->id,
            'warehouse_id' => $hanoiWarehouse->id,
            'full_name' => 'Tùng',
            'email' => 'tung@perw.com',
            'password' => bcrypt('password'),
            'phone_number' => '0900000004',
            'status' => 'active'
        ]);

        // Create regular users
        $alice = User::factory()->create([
            'role_id' => $userRole->id,
            'full_name' => 'Alice Tran',
            'email' => 'alice.tran@email.com',
            'password' => bcrypt('password'),
            'phone_number' => '0912345678',
            'status' => 'active'
        ]);

        $bob = User::factory()->create([
            'role_id' => $userRole->id,
            'full_name' => 'Bob Nguyen',
            'email' => 'bob.nguyen@email.com',
            'password' => bcrypt('password'),
            'phone_number' => '0987654321',
            'status' => 'active'
        ]);

        // Create addresses
        Address::factory()->create([
            'user_id' => $alice->id,
            'recipient_name' => 'Alice Tran',
            'recipient_phone' => '0912345678',
            'street_address' => '123 Đường ABC',
            'ward' => 'Phường 4',
            'district' => 'Quận 5',
            'city' => 'TP. Hồ Chí Minh',
            'is_default' => true
        ]);

        Address::factory()->create([
            'user_id' => $bob->id,
            'recipient_name' => 'Bob Nguyen',
            'recipient_phone' => '0987654321',
            'street_address' => '456 Đường XYZ',
            'ward' => 'Phường Cống Vị',
            'district' => 'Quận Ba Đình',
            'city' => 'Hà Nội',
            'is_default' => true
        ]);

        Address::factory()->create([
            'user_id' => $bob->id,
            'recipient_name' => 'Văn phòng Bob',
            'recipient_phone' => '0987654321',
            'street_address' => '789 Tòa nhà Lotte',
            'ward' => 'Phường Liễu Giai',
            'district' => 'Quận Ba Đình',
            'city' => 'Hà Nội',
            'is_default' => false
        ]);

        // Create products
        $gojoProduct = Product::factory()->create([
            'category_id' => $nendoroidCategory->id,
            'supplier_id' => $goodSmile->id,
            'name' => 'Nendoroid Gojo Satoru - Jujutsu Kaisen',
            'description' => 'Mô hình Nendoroid của chú thuật sư mạnh nhất Gojo Satoru.',
            'slug' => 'nendoroid-gojo-satoru-jujutsu-kaisen',
            'status' => 'published'
        ]);

        $leviProduct = Product::factory()->create([
            'category_id' => $scaleFigureCategory->id,
            'supplier_id' => $kotobukiya->id,
            'name' => 'Scale Figure Levi Ackerman - Attack on Titan',
            'description' => 'Mô hình tỷ lệ 1/7 của Đội trưởng Levi trong tư thế chiến đấu.',
            'slug' => 'scale-figure-levi-ackerman-attack-on-titan',
            'status' => 'published'
        ]);

        $shirtProduct = Product::factory()->create([
            'category_id' => $apparelCategory->id,
            'supplier_id' => $uniqlo->id,
            'name' => 'Áo Thun UT Spy x Family',
            'description' => 'Áo thun hợp tác giữa Uniqlo và series Spy x Family.',
            'slug' => 'ut-tshirt-spy-x-family',
            'status' => 'published'
        ]);

        // Create product variants
        $gojoVariant = ProductVariant::factory()->create([
            'product_id' => $gojoProduct->id,
            'name' => 'Standard Ver.',
            'sku' => 'GSC-NENDO-1528',
            'price' => 1200000.00,
            'original_price' => 1350000.00
        ]);

        $leviVariant = ProductVariant::factory()->create([
            'product_id' => $leviProduct->id,
            'name' => 'Fortitude Ver.',
            'sku' => 'KTK-AOT-LEVI-01',
            'price' => 3500000.00
        ]);

        $shirtVariantS = ProductVariant::factory()->create([
            'product_id' => $shirtProduct->id,
            'name' => 'Size S',
            'sku' => 'UT-SPY-TSHIRT-S',
            'price' => 450000.00
        ]);

        $shirtVariantM = ProductVariant::factory()->create([
            'product_id' => $shirtProduct->id,
            'name' => 'Size M',
            'sku' => 'UT-SPY-TSHIRT-M',
            'price' => 450000.00
        ]);

        $shirtVariantL = ProductVariant::factory()->create([
            'product_id' => $shirtProduct->id,
            'name' => 'Size L',
            'sku' => 'UT-SPY-TSHIRT-L',
            'price' => 450000.00
        ]);

        // Create sample order
        $order = PurchaseOrder::factory()->create([
            'user_id' => $bob->id,
            'order_code' => 'PERW-20230001',
            'status' => 'shipped',
            'shipping_recipient_name' => 'Bob Nguyen',
            'shipping_recipient_phone' => '0987654321',
            'shipping_address' => '456 Đường XYZ, Phường Cống Vị, Quận Ba Đình, Hà Nội',
            'sub_total' => 3500000.00,
            'shipping_fee' => 30000.00,
            'discount_amount' => 0.00,
            'total_amount' => 3530000.00
        ]);
    }
}

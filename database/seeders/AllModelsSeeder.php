<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\User;
use App\Models\Address;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\ShippingMethod;
use App\Models\Shipment;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Hash;

class AllModelsSeeder extends Seeder
{
	/**
	 * Seed all application models using their factories.
	 */
	public function run(): void
	{
        // Ensure required roles exist
        $roles = Role::whereIn('name', ['admin', 'manager', 'endUser'])->get();
        if ($roles->count() !== 3) {
            throw new \RuntimeException('Expected roles not found. Run RoleSeeder first.');
        }

        $endUserRoleId = $roles->firstWhere('name', 'endUser')->id;

        // Create sample end users if they don't exist
        $customerDefinitions = [
            [
                'email' => 'customer1@perw.com',
                'name' => 'Mai Tran',
                'full_name' => 'Mai Tran',
                'phone_number' => '0900000001',
                'city' => 'TP.HCM',
                'address' => '123 Nguyen Hue, Quan 1, TP.HCM',
            ],
            [
                'email' => 'customer2@perw.com',
                'name' => 'Nam Nguyen',
                'full_name' => 'Nam Nguyen',
                'phone_number' => '0900000002',
                'city' => 'Ha Noi',
                'address' => '45 Pho Hue, Quan Hai Ba Trung, Ha Noi',
            ],
            [
                'email' => 'customer3@perw.com',
                'name' => 'Linh Pham',
                'full_name' => 'Linh Pham',
                'phone_number' => '0900000003',
                'city' => 'Da Nang',
                'address' => '88 Bach Dang, Hai Chau, Da Nang',
            ],
            [
                'email' => 'customer4@perw.com',
                'name' => 'Quang Le',
                'full_name' => 'Quang Le',
                'phone_number' => '0900000004',
                'city' => 'Can Tho',
                'address' => '12 Ly Tu Trong, Ninh Kieu, Can Tho',
            ],
            [
                'email' => 'customer5@perw.com',
                'name' => 'Thao Do',
                'full_name' => 'Thao Do',
                'phone_number' => '0900000005',
                'city' => 'Hai Phong',
                'address' => '67 Tran Phu, Hong Bang, Hai Phong',
            ],
        ];

        foreach ($customerDefinitions as $customer) {
            User::updateOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'full_name' => $customer['full_name'],
                    'password' => Hash::make('password'),
                    'role_id' => $endUserRoleId,
                    'status' => 'active',
                    'warehouse_id' => null,
                    'phone_number' => $customer['phone_number'],
                ]
            );
        }

        // Create additional end users if needed
        $existingEndUsers = User::where('role_id', $endUserRoleId)->count();
        $targetEndUsers = 30;
        if ($existingEndUsers < $targetEndUsers) {
            // Create users with unique emails to avoid duplicates
            for ($i = 0; $i < ($targetEndUsers - $existingEndUsers); $i++) {
                $email = 'user' . time() . $i . '@perw.com';
                User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => fake()->name(),
                        'full_name' => fake()->name(),
                        'password' => Hash::make('password'),
                        'role_id' => $endUserRoleId,
                        'status' => 'active',
                        'warehouse_id' => null,
                        'phone_number' => '09' . fake()->numerify('########'),
                    ]
                );
            }
        }

        // Create suppliers
        $supplierDefinitions = [
            ['name' => 'Good Smile Company', 'contact_info' => 'Premier Japanese figure manufacturer specializing in Nendoroids and scale collectibles.'],
            ['name' => 'Kotobukiya', 'contact_info' => 'High quality scale figures and model kits from Japan.'],
            ['name' => 'Bandai Spirits', 'contact_info' => 'Model kits and trading collectibles for top anime franchises.'],
            ['name' => 'Banpresto', 'contact_info' => 'Affordably priced prize figures and seasonal merchandise.'],
            ['name' => 'SEGA Merchandising', 'contact_info' => 'Official apparel and accessories licensed by SEGA.'],
            ['name' => 'Aniplex Plus', 'contact_info' => 'Limited edition anime goods curated by Aniplex.'],
        ];

        $suppliers = [];
        foreach ($supplierDefinitions as $definition) {
            $suppliers[$definition['name']] = Supplier::updateOrCreate(
                ['name' => $definition['name']],
                ['contact_info' => $definition['contact_info']]
            );
        }

        // Create categories
        $categoryDefinitions = [
            [
                'name' => 'Figures',
                'slug' => 'figures',
                'children' => [
                    ['name' => 'Nendoroid', 'slug' => 'nendoroid'],
                    ['name' => 'Scale Figure', 'slug' => 'scale-figure'],
                    ['name' => 'Prize Figure', 'slug' => 'prize-figure'],
                ],
            ],
            [
                'name' => 'Merchandise',
                'slug' => 'merchandise',
                'children' => [
                    ['name' => 'Apparel', 'slug' => 'apparel'],
                    ['name' => 'Accessories', 'slug' => 'accessories'],
                    ['name' => 'Home Goods', 'slug' => 'home-goods'],
                ],
            ],
            [
                'name' => 'Collectibles',
                'slug' => 'collectibles',
                'children' => [
                    ['name' => 'Trading Cards', 'slug' => 'trading-cards'],
                    ['name' => 'Model Kits', 'slug' => 'model-kits'],
                ],
            ],
            [
                'name' => 'Books & Media',
                'slug' => 'books-media',
                'children' => [
                    ['name' => 'Manga', 'slug' => 'manga'],
                    ['name' => 'Light Novel', 'slug' => 'light-novel'],
                ],
            ],
        ];

        $categories = [];
        foreach ($categoryDefinitions as $definition) {
            $category = Category::updateOrCreate(
                ['slug' => $definition['slug']],
                ['name' => $definition['name'], 'parent_id' => null]
            );
            $categories[$definition['slug']] = $category;

            foreach ($definition['children'] as $childDefinition) {
                $child = Category::updateOrCreate(
                    ['slug' => $childDefinition['slug']],
                    ['name' => $childDefinition['name'], 'parent_id' => $category->id]
                );
                $categories[$childDefinition['slug']] = $child;
            }
        }

        // Create shipping methods
        $shippingMethodDefinitions = [
            ['name' => 'Standard Delivery (Nationwide)', 'cost' => 35000, 'is_active' => true],
            ['name' => 'Express Delivery (Nationwide)', 'cost' => 80000, 'is_active' => true],
            ['name' => 'Same Day Delivery (TP.HCM)', 'cost' => 50000, 'is_active' => true],
            ['name' => 'Store Pickup (Ha Noi)', 'cost' => 0, 'is_active' => true],
        ];

        foreach ($shippingMethodDefinitions as $definition) {
            ShippingMethod::updateOrCreate(
                ['name' => $definition['name']],
                ['cost' => $definition['cost'], 'is_active' => $definition['is_active']]
            );
        }

        // Create payment methods
        $paymentMethodDefinitions = [
            ['name' => 'Cash on Delivery', 'code' => 'COD', 'is_active' => true],
            ['name' => 'MoMo E-Wallet', 'code' => 'MOMO', 'is_active' => true],
            ['name' => 'VNPay QR', 'code' => 'VNPAY', 'is_active' => true],
            ['name' => 'Credit Card', 'code' => 'CARD', 'is_active' => true],
            ['name' => 'Bank Transfer', 'code' => 'BANK', 'is_active' => true],
        ];

        foreach ($paymentMethodDefinitions as $definition) {
            PaymentMethod::updateOrCreate(
                ['name' => $definition['name'], 'code' => $definition['code']],
                ['is_active' => $definition['is_active']]
            );
        }

        // Create sample products
        $catalog = [
            [
                'name' => 'Tanjiro Kamado Nendoroid 1528',
                'slug' => 'tanjiro-kamado-nendoroid-1528',
                'description' => 'Official Good Smile Company Nendoroid featuring Tanjiro with multiple face plates and accessories.',
                'category_slug' => 'nendoroid',
                'supplier_name' => 'Good Smile Company',
                'images' => ['/images/products/product-1.svg', '/images/products/product-2.svg'],
                'variants' => [
                    ['name' => 'Standard Edition', 'sku' => 'GSC-NEN-1528-STD', 'price' => 1299000, 'original_price' => 1499000],
                    ['name' => 'Deluxe Edition', 'sku' => 'GSC-NEN-1528-DLX', 'price' => 1799000, 'original_price' => 1999000],
                ],
            ],
            [
                'name' => 'Nezuko Kamado 1/7 Scale Figure',
                'slug' => 'nezuko-kamado-scale-figure',
                'description' => 'Detailed 1/7 scale figure crafted by Kotobukiya with dynamic flame base.',
                'category_slug' => 'scale-figure',
                'supplier_name' => 'Kotobukiya',
                'images' => ['/images/products/product-3.svg', '/images/products/product-4.svg'],
                'variants' => [
                    ['name' => 'Standard Release', 'sku' => 'KTB-SCL-NEZUKO', 'price' => 3499000, 'original_price' => 3699000],
                ],
            ],
            [
                'name' => 'Attack on Titan Eren Yeager Statue',
                'slug' => 'eren-yeager-statue',
                'description' => 'Premium cold cast statue depicting Eren\'s iconic Titan form.',
                'category_slug' => 'scale-figure',
                'supplier_name' => 'Bandai Spirits',
                'images' => ['/images/products/product-5.svg', '/images/products/product-6.svg'],
                'variants' => [
                    ['name' => 'Limited Edition', 'sku' => 'BND-STA-EREN', 'price' => 4999000, 'original_price' => 5499000],
                ],
            ],
            [
                'name' => 'My Hero Academia Deku Nendoroid',
                'slug' => 'deku-nendoroid',
                'description' => 'Adorable Midoriya Izuku with One For All power effect.',
                'category_slug' => 'nendoroid',
                'supplier_name' => 'Good Smile Company',
                'images' => ['/images/products/product-7.svg', '/images/products/product-8.svg'],
                'variants' => [
                    ['name' => 'Standard', 'sku' => 'GSC-NEN-DEKU', 'price' => 1199000, 'original_price' => 1299000],
                ],
            ],
            [
                'name' => 'Demon Slayer Kimetsu no Yaiba Hoodie',
                'slug' => 'kimetsu-hoodie',
                'description' => 'Official licensed hoodie featuring the iconic Demon Slayer pattern.',
                'category_slug' => 'apparel',
                'supplier_name' => 'SEGA Merchandising',
                'images' => ['/images/products/product-9.svg', '/images/products/product-10.svg'],
                'variants' => [
                    ['name' => 'Size M', 'sku' => 'SGA-APP-HOODIE-M', 'price' => 799000, 'original_price' => 899000],
                    ['name' => 'Size L', 'sku' => 'SGA-APP-HOODIE-L', 'price' => 799000, 'original_price' => 899000],
                    ['name' => 'Size XL', 'sku' => 'SGA-APP-HOODIE-XL', 'price' => 799000, 'original_price' => 899000],
                ],
            ],
        ];

        foreach ($catalog as $productData) {
            $category = $categories[$productData['category_slug']] ?? null;
            $supplier = $suppliers[$productData['supplier_name']] ?? null;

            if (!$category || !$supplier) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'category_id' => $category->id,
                    'supplier_id' => $supplier->id,
                ]
            );

            foreach ($productData['variants'] as $variantData) {
                $variant = ProductVariant::updateOrCreate(
                    ['sku' => $variantData['sku']],
                    [
                        'product_id' => $product->id,
                        'name' => $variantData['name'],
                        'price' => $variantData['price'],
                        'original_price' => $variantData['original_price'],
                        // Removed stock_quantity since it's not a real column
                    ]
                );
            }

            // Create product images
            foreach ($productData['images'] as $imageUrl) {
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'image_url' => $imageUrl],
                    ['is_primary' => false]
                );
            }

            // Set first image as primary
            $firstImage = $product->images()->first();
            if ($firstImage) {
                $firstImage->update(['is_primary' => true]);
            }
        }

        // Create additional products using factories
        $existingProducts = Product::count();
        $targetProducts = 50;
        if ($existingProducts < $targetProducts) {
            Product::factory()
                ->count($targetProducts - $existingProducts)
                ->create();

            // Create variants for the additional products
            $additionalProducts = Product::skip($existingProducts)->take($targetProducts - $existingProducts)->get();
            foreach ($additionalProducts as $product) {
                ProductVariant::factory()
                    ->count(fake()->numberBetween(1, 3))
                    ->create(['product_id' => $product->id]);
            }
        }

        // Create product images for additional products
        $existingImages = ProductImage::count();
        $targetImages = 150;
        if ($existingImages < $targetImages) {
            ProductImage::factory()
                ->count($targetImages - $existingImages)
                ->create();
        }

        // Create product reviews
        $existingReviews = ProductReview::count();
        $targetReviews = 100;
        if ($existingReviews < $targetReviews) {
            ProductReview::factory()
                ->count($targetReviews - $existingReviews)
                ->create();
        }

        // Create carts
        Cart::factory()->count(20)->create();
        CartDetail::factory()->count(50)->create();

        // Create inventory transactions
        InventoryTransaction::factory()->count(100)->create();
	}
}

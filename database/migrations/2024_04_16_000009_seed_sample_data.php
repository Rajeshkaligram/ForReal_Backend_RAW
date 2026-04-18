<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed categories
        $categories = [
            ['name' => 'Suits', 'picture' => 'uploads/categories/suits.jpg', 'seo_url' => 'suits', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Blazers', 'picture' => 'uploads/categories/blazers.jpg', 'seo_url' => 'blazers', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tuxedos', 'picture' => 'uploads/categories/tuxedos.jpg', 'seo_url' => 'tuxedos', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Formal Shirts', 'picture' => 'uploads/categories/shirts.jpg', 'seo_url' => 'formal-shirts', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dress Pants', 'picture' => 'uploads/categories/pants.jpg', 'seo_url' => 'dress-pants', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['seo_url' => $category['seo_url']],
                $category
            );
        }

        // Get category IDs
        $suitCategoryId = DB::table('categories')->where('seo_url', 'suits')->value('id');
        $blazerCategoryId = DB::table('categories')->where('seo_url', 'blazers')->value('id');

        // Create a test user if not exists
        $userId = DB::table('users')->updateOrInsert(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'status' => 1,
                'privilege' => 1,
                'location' => 'New York',
                'height' => '5\'10"',
                'body_type' => 'Athletic',
                'profile_picture' => 'uploads/others/no_avatar.jpg',
                'profile_picture_custom_size' => 'uploads/others/no_avatar.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($userId) {
            $userId = DB::table('users')->where('email', 'test@example.com')->value('id');
        }

        // Seed sample products
        $products = [
            [
                'name' => 'Classic Black Suit',
                'price' => 150.00,
                'picture' => 'uploads/products/suit1.jpg',
                'size' => 'M',
                'designer' => 'Armani',
                'season' => 'All Season',
                'description' => 'A classic black suit perfect for any formal occasion.',
                'seo_url' => 'classic-black-suit',
                'is_deleted' => 0,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Navy Blue Blazer',
                'price' => 120.00,
                'picture' => 'uploads/products/blazer1.jpg',
                'size' => 'L',
                'designer' => 'Hugo Boss',
                'season' => 'Spring',
                'description' => 'Elegant navy blue blazer for business or casual wear.',
                'seo_url' => 'navy-blue-blazer',
                'is_deleted' => 0,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Formal White Shirt',
                'price' => 80.00,
                'picture' => 'uploads/products/shirt1.jpg',
                'size' => 'M',
                'designer' => 'Calvin Klein',
                'season' => 'All Season',
                'description' => 'Crisp white formal shirt for professional wear.',
                'seo_url' => 'formal-white-shirt',
                'is_deleted' => 0,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($products as $index => $product) {
            $productId = DB::table('products')->updateOrInsert(
                ['seo_url' => $product['seo_url']],
                $product
            );

            if (!$productId) {
                $productId = DB::table('products')->where('seo_url', $product['seo_url'])->value('id');
            } else {
                $productId = DB::table('products')->where('seo_url', $product['seo_url'])->value('id');
            }

            // Assign categories to products
            if ($productId) {
                if ($index === 0 && $suitCategoryId) {
                    DB::table('product_categories')->updateOrInsert(
                        ['product_id' => $productId, 'category_id' => $suitCategoryId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                } elseif ($index === 1 && $blazerCategoryId) {
                    DB::table('product_categories')->updateOrInsert(
                        ['product_id' => $productId, 'category_id' => $blazerCategoryId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                } elseif ($index === 2 && $suitCategoryId) {
                    DB::table('product_categories')->updateOrInsert(
                        ['product_id' => $productId, 'category_id' => $suitCategoryId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove sample data
        DB::table('product_categories')->whereIn('product_id', function($query) {
            $query->select('id')->from('products')->whereIn('seo_url', ['classic-black-suit', 'navy-blue-blazer', 'formal-white-shirt']);
        })->delete();

        DB::table('products')->whereIn('seo_url', ['classic-black-suit', 'navy-blue-blazer', 'formal-white-shirt'])->delete();
        DB::table('users')->where('email', 'test@example.com')->delete();
        DB::table('categories')->whereIn('seo_url', ['suits', 'blazers', 'tuxedos', 'formal-shirts', 'dress-pants'])->delete();
    }
};

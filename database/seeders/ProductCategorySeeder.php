<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product_categories = [
            'Art & Crafts',
            'Beauty',
            'Clothing',
            'Computers',
            'Drinks',
            'Food',
            'Electronics',
            'Home & Living',
            'Health',
            'Phones',
            'Shoes'
        ];

        foreach ($product_categories as $category) {
            ProductCategory::create([
                'name' => $category,
            ]);
        }
    }
}

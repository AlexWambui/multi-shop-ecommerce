<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Resources\Products\ProductDetailsPageResource;

class GuestProductController extends Controller
{
    public function productDetails(Product $product)
    {
        $product->load('shop', 'category', 'images');

        return inertia('guest/products/ProductDetails', [
            'product' => (new ProductDetailsPageResource($product))->resolve(),
            'reviews' => [],
            'related_products' => []
        ]);
    }
}

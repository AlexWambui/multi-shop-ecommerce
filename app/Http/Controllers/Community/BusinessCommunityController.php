<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\BusinessPost;
use App\Http\Resources\Community\BusinessPostResource;

class BusinessCommunityController extends Controller
{
    public function index()
    {
        $shops = Shop::get();
        $business_posts = BusinessPost::latest()->paginate(10);

        return inertia('app/community/business/Index', [
            'shops' => $shops,
            'business_posts' => BusinessPostResource::collection($business_posts)
        ]);
    }
}

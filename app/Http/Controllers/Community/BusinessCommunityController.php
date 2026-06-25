<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Shop;
use App\Models\BusinessPost;
use App\Models\BusinessChatMessage;
use App\Http\Resources\Community\BusinessPostResource;

class BusinessCommunityController extends Controller
{
    public function user(): User
    {
        return Auth::user();
    }

    public function index()
    {
        $user = $this->user();

        $shops = Shop::get();

        $business_posts = BusinessPost::with(['shop', 'comments' => function ($query) {
            $query->latest()->limit(3);
        }])
        ->withCount(['likes', 'comments'])
        ->latest()
        ->paginate(10);

        $chat_messages = BusinessChatMessage::with('shop')->orderBy('created_at', 'asc')->get();

        $shop = $user->shops()->first();

        return inertia('app/community/business/Index', [
            'shops' => $shops,
            'shop' => $shop,
            'chat_messages' => $chat_messages,
            'business_posts' => BusinessPostResource::collection($business_posts)
        ]);
    }
}

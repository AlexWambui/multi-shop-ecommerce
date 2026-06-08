<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\User;
use App\Models\BusinessPost;

class BusinessPostController extends Controller
{
    public function user(): User
    {
        return Auth::user();
    }

    public function create()
    {
        $user = $this->user();

        $shops = $user->shops()
            ->orderBy('name')
            ->get(['id', 'name']);

        // Handle case with no shops
        if ($shops->isEmpty()) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => "You need to first create a shop"
            ]);

            return to_route('my-shops.create');
        }

        // For single shop, preselect it
        $selectedShopId = $shops->count() === 1 ? $shops->first()->id : null;

        return inertia('app/community/business/posts/Create', [
            'shops' => $shops,
            'selectedShopId' => $selectedShopId
        ]);
    }

    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'content' => 'required|string|min:3|max:5000',
            'is_active' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120' // Max 5MB
        ]);

        // Verify the shop belongs to the user
        $shop = $this->user()->shops()->findOrFail($validated['shop_id']);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('business-posts', $filename, 'public');
        }

        // Create the post
        $post = BusinessPost::create([
            'shop_id' => $shop->id,
            'content' => $validated['content'],
            'image' => $imagePath, // Save the image path
            'is_draft' => !($validated['is_active'] ?? true),
            'published_at' => now(),
        ]);

        // Redirect to the community page with success message
        return redirect()->route('business-community.index')
            ->with('success', 'Post created successfully!');
    }
}

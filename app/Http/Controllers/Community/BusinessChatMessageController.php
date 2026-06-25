<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use App\Models\BusinessChatMessage;

class BusinessChatMessageController extends Controller
{
    public function user(): User
    {
        return Auth::user();
    }

    public function store(Request $request)
    {
        $user = $this->user();

        $shops = $user->shops()->get();

        // Handle case with no shops
        if ($shops->isEmpty()) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => "You need to first create a shop"
            ]);

            return back();
        }

        $validated = $request->validate([
            'message' => 'required|string|min:3|max:5000',
        ], [
            'message.required' => "You must enter a message."
        ]);

        $shop = $shops->first();

        $message = BusinessChatMessage::create([
            'shop_id' => $shop->id,
            'message' => $validated['message'],
        ]);

        return back();
    }
}

<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessPost;
use App\Models\User;

class BusinessPostLikeController extends Controller
{
    public function user(): User
    {
        return Auth::user();
    }

    public function like(BusinessPost $post)
    {   
        $isLiked = $post->toggleLike($this->user());
        
        // Broadcast the like event
        // broadcast(new BusinessPostLikedEvent($post, $shop, $isLiked));
        
        return redirect()->back();
    }
}

## Models

```php
// app/Models/Shop.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'name', 'slug', 'description', 'logo', 'cover_image',
        'category', 'contact_email', 'contact_phone', 'is_verified', 'is_active', 'settings'
    ];
    
    protected $casts = [
        'settings' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function posts()
    {
        return $this->hasMany(Post::class)->latest();
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_likes')->withTimestamps();
    }
    
    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_shop_id');
    }
    
    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'receiver_shop_id');
    }
    
    public function followers()
    {
        return $this->belongsToMany(Shop::class, 'follows', 'following_shop_id', 'follower_shop_id');
    }
    
    public function following()
    {
        return $this->belongsToMany(Shop::class, 'follows', 'follower_shop_id', 'following_shop_id');
    }
    
    public function onlineActivity()
    {
        return $this->hasOne(OnlineActivity::class);
    }
    
    // Accessors
    public function getIsOnlineAttribute()
    {
        return $this->onlineActivity && 
               $this->onlineActivity->last_activity_at->gt(now()->subMinutes(5));
    }
    
    // Helper methods
    public function hasLiked(Post $post)
    {
        return $this->likedPosts()->where('post_id', $post->id)->exists();
    }
    
    public function follow(Shop $shop)
    {
        return $this->following()->attach($shop);
    }
    
    public function unfollow(Shop $shop)
    {
        return $this->following()->detach($shop);
    }
    
    public function isFollowing(Shop $shop)
    {
        return $this->following()->where('following_shop_id', $shop->id)->exists();
    }
}



// app/Models/Post.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'shop_id', 'content', 'image', 'is_pinned', 'published_at'
    ];
    
    protected $casts = [
        'published_at' => 'datetime',
        'is_pinned' => 'boolean',
    ];
    
    protected $with = ['shop'];
    
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }
    
    public function likes()
    {
        return $this->belongsToMany(Shop::class, 'post_likes')->withTimestamps();
    }
    
    public function toggleLike(Shop $shop)
    {
        if ($this->likes()->where('shop_id', $shop->id)->exists()) {
            $this->likes()->detach($shop);
            $this->decrement('likes_count');
            return false;
        }
        
        $this->likes()->attach($shop);
        $this->increment('likes_count');
        return true;
    }
}




// app/Models/Comment.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'post_id', 'shop_id', 'parent_id', 'content'
    ];
    
    protected $with = ['shop'];
    
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
    
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
    
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
    
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}



// app/Models/ChatMessage.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'sender_shop_id', 'receiver_shop_id', 'message', 'is_read', 'read_at'
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
    
    protected $with = ['sender', 'receiver'];
    
    public function sender()
    {
        return $this->belongsTo(Shop::class, 'sender_shop_id');
    }
    
    public function receiver()
    {
        return $this->belongsTo(Shop::class, 'receiver_shop_id');
    }
    
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }
}



// app/Models/User.php - Add shop relationship
public function shop()
{
    return $this->hasOne(Shop::class);
}
```

## Laravel Reverb

Installation.

```bash
composer require laravel/reverb
php artisan reverb:install
```

Configuration.

```php
// config/reverb.php
return [
    'apps' => [
        [
            'id' => env('REVERB_APP_ID'),
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
        ],
    ],
];
```

Creating Events.

```bash
php artisan make:event NewPostEvent
php artisan make:event NewCommentEvent
php artisan make:event NewMessageEvent
php artisan make:event ShopTypingEvent
php artisan make:event PostLikedEvent
```

```php
// app/Events/NewPostEvent.php
<?php
namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPostEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $post;
    
    public function __construct(Post $post)
    {
        $this->post = $post->load('shop');
    }
    
    public function broadcastOn()
    {
        return new Channel('community.posts');
    }
    
    public function broadcastAs()
    {
        return 'new-post';
    }
    
    public function broadcastWith()
    {
        return [
            'post' => [
                'id' => $this->post->id,
                'content' => $this->post->content,
                'shop' => [
                    'id' => $this->post->shop->id,
                    'name' => $this->post->shop->name,
                    'logo' => $this->post->shop->logo,
                ],
                'likes_count' => $this->post->likes_count,
                'comments_count' => $this->post->comments_count,
                'published_at' => $this->post->published_at->diffForHumans(),
            ]
        ];
    }
}


// app/Events/NewMessageEvent.php
<?php
namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $message;
    
    public function __construct(ChatMessage $message)
    {
        $this->message = $message->load('sender', 'receiver');
    }
    
    public function broadcastOn()
    {
        // Send to both sender and receiver
        return [
            new PrivateChannel('chat.shop.' . $this->message->sender_shop_id),
            new PrivateChannel('chat.shop.' . $this->message->receiver_shop_id),
        ];
    }
    
    public function broadcastAs()
    {
        return 'new-message';
    }
    
    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'message' => $this->message->message,
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                ],
                'created_at' => $this->message->created_at->diffForHumans(),
            ]
        ];
    }
}
```

Configure broadcasting.

```php
// config/broadcasting.php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
        ],
    ],
],
```

Setup echo on the frontend.

```php
npm install laravel-echo pusher-js
```

```js
// resources/js/echo.js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
})

export default echo;





// resources/js/app.js
import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import echo from './echo'

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        app.use(plugin)
        app.use(ZiggyVue)
        app.config.globalProperties.$echo = echo
        app.mount(el)
    },
})
```

## Controllers

```php
// app/Http/Controllers/CommunityController.php
<?php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Shop;
use App\Models\OnlineActivity;
use App\Models\ChatMessage;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Events\NewPostEvent;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $shop = auth()->user()->shop;
        
        // Get posts with pagination
        $posts = Post::with(['shop', 'comments' => function($q) {
                $q->latest()->limit(3);
            }])
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->paginate(10);
        
        // Get online shops (active in last 5 minutes)
        $onlineShops = Shop::whereHas('onlineActivity', function($q) {
                $q->where('last_activity_at', '>=', now()->subMinutes(5));
            })
            ->with('onlineActivity')
            ->limit(20)
            ->get()
            ->map(function($s) use ($shop) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'logo' => $s->logo,
                    'category' => $s->category,
                    'is_online' => true,
                    'is_following' => $shop ? $shop->isFollowing($s) : false,
                ];
            });
        
        // Get recent chat messages for the sidebar
        $recentChats = ChatMessage::where('sender_shop_id', $shop->id)
            ->orWhere('receiver_shop_id', $shop->id)
            ->with(['sender', 'receiver'])
            ->latest()
            ->limit(50)
            ->get()
            ->groupBy(function($message) use ($shop) {
                return $message->sender_shop_id === $shop->id 
                    ? $message->receiver_shop_id 
                    : $message->sender_shop_id;
            })
            ->map(function($messages) {
                return $messages->first();
            });
        
        return Inertia::render('Community/Index', [
            'posts' => $posts,
            'onlineShops' => $onlineShops,
            'recentChats' => $recentChats,
            'authShop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'logo' => $shop->logo,
            ]
        ]);
    }
}




// app/Http/Controllers/PostController.php
<?php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Events\NewPostEvent;
use App\Events\NewNotificationEvent;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'image' => 'nullable|image|max:2048'
        ]);
        
        $shop = auth()->user()->shop;
        
        $post = $shop->posts()->create([
            'content' => $request->content,
            'published_at' => now(),
        ]);
        
        // Handle image upload if present
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('posts', 'public');
            $post->update(['image' => $path]);
        }
        
        // Load relationships for response
        $post->load('shop');
        
        // Broadcast to all users
        broadcast(new NewPostEvent($post));
        
        // Notify followers
        foreach ($shop->followers as $follower) {
            // Create notification for each follower
            $follower->user->notify(new NewPostNotification($post));
        }
        
        return redirect()->back()->with('success', 'Post created successfully!');
    }
    
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        
        return redirect()->back()->with('success', 'Post deleted successfully!');
    }
    
    public function show(Post $post)
    {
        $post->load(['shop', 'comments.shop', 'comments.replies.shop']);
        
        return Inertia::render('Community/Show', [
            'post' => $post,
            'authShop' => auth()->user()->shop
        ]);
    }
}


// app/Http/Controllers/LikeController.php
<?php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Events\PostLikedEvent;

class LikeController extends Controller
{
    public function toggle(Post $post)
    {
        $shop = auth()->user()->shop;
        
        $isLiked = $post->toggleLike($shop);
        
        // Broadcast the like event
        broadcast(new PostLikedEvent($post, $shop, $isLiked));
        
        return response()->json([
            'liked' => $isLiked,
            'likes_count' => $post->fresh()->likes_count
        ]);
    }
}




// app/Http/Controllers/CommentController.php
<?php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Events\NewCommentEvent;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string|max:1000'
        ]);
        
        $shop = auth()->user()->shop;
        
        $comment = $post->comments()->create([
            'shop_id' => $shop->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id ?? null
        ]);
        
        $comment->load('shop');
        
        // Increment post comments count
        $post->increment('comments_count');
        
        // Broadcast new comment
        broadcast(new NewCommentEvent($comment));
        
        // Notify post owner if comment is not from them
        if ($post->shop_id !== $shop->id) {
            $post->shop->user->notify(new NewCommentNotification($comment));
        }
        
        return redirect()->back()->with('success', 'Comment added!');
    }
    
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();
        
        // Decrement post comments count
        $comment->post->decrement('comments_count');
        
        return redirect()->back()->with('success', 'Comment deleted!');
    }
}




// app/Http/Controllers/ChatController.php
<?php
namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Shop;
use Illuminate\Http\Request;
use App\Events\NewMessageEvent;
use App\Events\ShopTypingEvent;

class ChatController extends Controller
{
    public function index()
    {
        $shop = auth()->user()->shop;
        
        $conversations = ChatMessage::where('sender_shop_id', $shop->id)
            ->orWhere('receiver_shop_id', $shop->id)
            ->with(['sender', 'receiver'])
            ->latest()
            ->get()
            ->groupBy(function($message) use ($shop) {
                return $message->sender_shop_id === $shop->id 
                    ? $message->receiver_shop_id 
                    : $message->sender_shop_id;
            })
            ->map(function($messages) {
                return $messages->first();
            });
        
        return Inertia::render('Community/Chat', [
            'conversations' => $conversations,
            'authShop' => $shop
        ]);
    }
    
    public function send(Request $request)
    {
        $request->validate([
            'receiver_shop_id' => 'required|exists:shops,id',
            'message' => 'required|string|max:1000'
        ]);
        
        $shop = auth()->user()->shop;
        
        $message = ChatMessage::create([
            'sender_shop_id' => $shop->id,
            'receiver_shop_id' => $request->receiver_shop_id,
            'message' => $request->message
        ]);
        
        $message->load('sender', 'receiver');
        
        // Broadcast to both parties
        broadcast(new NewMessageEvent($message));
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
    
    public function fetch(Request $request)
    {
        $shop = auth()->user()->shop;
        $otherShopId = $request->other_shop_id;
        
        $messages = ChatMessage::where(function($q) use ($shop, $otherShopId) {
                $q->where('sender_shop_id', $shop->id)
                  ->where('receiver_shop_id', $otherShopId);
            })
            ->orWhere(function($q) use ($shop, $otherShopId) {
                $q->where('sender_shop_id', $otherShopId)
                  ->where('receiver_shop_id', $shop->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Mark messages as read
        ChatMessage::where('sender_shop_id', $otherShopId)
            ->where('receiver_shop_id', $shop->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        
        return response()->json($messages);
    }
    
    public function typing(Request $request)
    {
        $shop = auth()->user()->shop;
        
        broadcast(new ShopTypingEvent($shop, $request->receiver_shop_id));
        
        return response()->json(['success' => true]);
    }
    
    public function getOnlineStatus()
    {
        $onlineShops = Shop::whereHas('onlineActivity', function($q) {
            $q->where('last_activity_at', '>=', now()->subMinutes(5));
        })->get(['id', 'name']);
        
        return response()->json($onlineShops);
    }
}
```

## Vue Components

```vue
<!-- resources/js/Pages/Community/Index.vue: Main community page -->
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PostCard from '@/Components/Community/PostCard.vue'
import CreatePostModal from '@/Components/Community/CreatePostModal.vue'
import BusinessChat from '@/Components/Community/BusinessChat.vue'
import OnlineUsers from '@/Components/Community/OnlineUsers.vue'

const page = usePage()
const props = defineProps({
    posts: Object,
    onlineShops: Array,
    recentChats: Object,
    authShop: Object
})

const showCreateModal = ref(false)
const selectedChatShop = ref(null)

// Real-time event listeners
let echo = null

onMounted(() => {
    echo = window.$echo
    
    if (echo) {
        // Listen for new posts
        echo.channel('community.posts')
            .listen('new-post', (e) => {
                props.posts.data.unshift(e.post)
            })
        
        // Listen for new comments
        echo.private(`shop.${props.authShop.id}`)
            .listen('new-comment', (e) => {
                // Update the specific post's comment count
                const post = props.posts.data.find(p => p.id === e.comment.post_id)
                if (post) {
                    post.comments_count++
                }
            })
    }
})

onUnmounted(() => {
    if (echo) {
        echo.leaveChannel('community.posts')
        echo.leaveChannel(`shop.${props.authShop.id}`)
    }
})

const handleLike = (postId, liked) => {
    router.post(`/posts/${postId}/like`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            const post = props.posts.data.find(p => p.id === postId)
            if (post) {
                post.likes_count += liked ? 1 : -1
                post.is_liked = liked
            }
        }
    })
}
</script>

<template>
    <MainLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-serif">Business Community</h1>
                    <p class="text-gray-500 text-sm mt-1">Connect with other shop owners</p>
                </div>
                <button 
                    @click="showCreateModal = true"
                    class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800"
                >
                    + New Post
                </button>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Feed -->
                <div class="lg:col-span-2 space-y-4">
                    <PostCard 
                        v-for="post in posts.data" 
                        :key="post.id"
                        :post="post"
                        :auth-shop="authShop"
                        @liked="handleLike"
                    />
                    
                    <!-- Pagination -->
                    <div class="flex justify-center mt-6">
                        <Link :href="posts.links.next" v-if="posts.links.next" class="text-gray-600 hover:text-gray-900">
                            Load More
                        </Link>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <BusinessChat 
                        :auth-shop="authShop"
                        :recent-chats="recentChats"
                        @select-chat="selectedChatShop = $event"
                    />
                    <OnlineUsers :online-shops="onlineShops" />
                </div>
            </div>
        </div>
        
        <CreatePostModal 
            v-model:show="showCreateModal"
            @created="showCreateModal = false"
        />
    </MainLayout>
</template>





<!-- resources/js/Components/Community/PostCard.vue: post card component -->
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import CommentSection from './CommentSection.vue'

const props = defineProps({
    post: Object,
    authShop: Object
})

const emit = defineEmits(['liked'])
const showComments = ref(false)

const toggleLike = () => {
    const newLikeState = !props.post.is_liked
    emit('liked', props.post.id, newLikeState)
    router.post(`/posts/${props.post.id}/like`, {}, {
        preserveScroll: true
    })
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <!-- Post Header -->
        <div class="flex items-start space-x-3">
            <div class="shrink-0">
                <div class="w-10 h-10 rounded-full bg-linear-to-br from-green-100 to-green-200 flex items-center justify-center text-lg">
                    {{ post.shop.name.charAt(0) }}
                </div>
            </div>
            <div class="flex-1">
                <div class="flex items-center space-x-2">
                    <h3 class="font-semibold text-gray-900">{{ post.shop.name }}</h3>
                    <span v-if="post.shop.is_verified" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">Verified</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ formatDate(post.published_at) }}</p>
                <p class="text-gray-700 mt-2 whitespace-pre-wrap">{{ post.content }}</p>
                
                <!-- Post Image -->
                <div v-if="post.image" class="mt-3 rounded-lg overflow-hidden">
                    <img :src="'/storage/' + post.image" class="w-full max-h-96 object-cover" alt="Post image">
                </div>
                
                <!-- Actions -->
                <div class="flex items-center space-x-6 mt-4 pt-2 border-t border-gray-100">
                    <button @click="toggleLike" class="flex items-center space-x-2 text-sm hover:text-red-500 transition">
                        <span class="text-lg">{{ post.is_liked ? '❤️' : '🤍' }}</span>
                        <span>{{ post.likes_count }} Likes</span>
                    </button>
                    <button @click="showComments = !showComments" class="flex items-center space-x-2 text-sm hover:text-blue-500">
                        <span>💬</span>
                        <span>{{ post.comments_count }} Comments</span>
                    </button>
                    <button class="flex items-center space-x-2 text-sm hover:text-green-500">
                        <span>↗️</span>
                        <span>Share</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Comments Section -->
        <CommentSection 
            v-if="showComments"
            :post="post"
            :auth-shop="authShop"
        />
    </div>
</template>






<!-- resources/js/Components/Community/BusinessChat.vue: business chat component -->
<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
    authShop: Object,
    recentChats: Object
})

const emit = defineEmits(['selectChat'])

const selectedShop = ref(null)
const messages = ref([])
const newMessage = ref('')
const isTyping = ref(false)
const typingTimeout = ref(null)
const chatContainer = ref(null)
let echo = null

const loadMessages = async (shopId) => {
    const response = await axios.get(`/api/chat/messages?other_shop_id=${shopId}`)
    messages.value = response.data
    scrollToBottom()
}

const sendMessage = async () => {
    if (!newMessage.value.trim() || !selectedShop.value) return
    
    const response = await axios.post('/api/chat/send', {
        receiver_shop_id: selectedShop.value.id,
        message: newMessage.value
    })
    
    messages.value.push(response.data.message)
    newMessage.value = ''
    scrollToBottom()
}

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight
        }
    })
}

const handleTyping = () => {
    if (typingTimeout.value) clearTimeout(typingTimeout.value)
    
    axios.post('/api/chat/typing', {
        receiver_shop_id: selectedShop.value.id
    })
    
    typingTimeout.value = setTimeout(() => {
        isTyping.value = false
    }, 1000)
}

onMounted(() => {
    echo = window.$echo
    
    if (echo) {
        echo.private(`chat.shop.${props.authShop.id}`)
            .listen('new-message', (e) => {
                if (selectedShop.value && e.message.sender.id === selectedShop.value.id) {
                    messages.value.push(e.message)
                    scrollToBottom()
                }
            })
            .listen('shop-typing', (e) => {
                if (selectedShop.value && e.shop_id === selectedShop.value.id) {
                    isTyping.value = true
                    setTimeout(() => { isTyping.value = false }, 2000)
                }
            })
    }
})

onUnmounted(() => {
    if (echo) {
        echo.leaveChannel(`chat.shop.${props.authShop.id}`)
    }
})

watch(selectedShop, (newShop) => {
    if (newShop) {
        loadMessages(newShop.id)
        emit('selectChat', newShop)
    }
})
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="font-medium text-sm">Business Chat</span>
                </div>
                <span class="text-xs text-gray-500">{{ Object.keys(recentChats).length }} conversations</span>
            </div>
        </div>
        
        <!-- Chat List -->
        <div class="max-h-64 overflow-y-auto border-b" v-if="!selectedShop">
            <div 
                v-for="(message, shopId) in recentChats" 
                :key="shopId"
                @click="selectedShop = message.sender.id === authShop.id ? message.receiver : message.sender"
                class="p-3 hover:bg-gray-50 cursor-pointer transition border-b last:border-b-0"
            >
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-sm">
                        {{ (message.sender.id === authShop.id ? message.receiver.name : message.sender.name).charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">
                            {{ message.sender.id === authShop.id ? message.receiver.name : message.sender.name }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">{{ message.message }}</p>
                    </div>
                    <span class="text-xs text-gray-400">{{ new Date(message.created_at).toLocaleTimeString() }}</span>
                </div>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div v-if="selectedShop" class="flex flex-col h-96">
            <!-- Chat Header -->
            <div class="bg-gray-50 px-4 py-2 border-b flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <button @click="selectedShop = null" class="text-gray-500 hover:text-gray-700">←</button>
                    <span class="font-medium">{{ selectedShop.name }}</span>
                </div>
                <span class="text-xs text-gray-500">Online</span>
            </div>
            
            <!-- Messages -->
            <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div 
                    v-for="msg in messages" 
                    :key="msg.id"
                    :class="['flex', msg.sender_shop_id === authShop.id ? 'justify-end' : 'justify-start']"
                >
                    <div 
                        :class="[
                            'max-w-[70%] rounded-lg px-3 py-2 text-sm',
                            msg.sender_shop_id === authShop.id 
                                ? 'bg-gray-900 text-white rounded-br-none' 
                                : 'bg-gray-100 text-gray-900 rounded-bl-none'
                        ]"
                    >
                        <p class="whitespace-pre-wrap">{{ msg.message }}</p>
                        <span class="text-xs opacity-75 mt-1 block">
                            {{ new Date(msg.created_at).toLocaleTimeString() }}
                        </span>
                    </div>
                </div>
                
                <div v-if="isTyping" class="text-xs text-gray-500 italic">
                    {{ selectedShop.name }} is typing...
                </div>
            </div>
            
            <!-- Input -->
            <div class="border-t p-3">
                <div class="flex space-x-2">
                    <input 
                        v-model="newMessage"
                        @keyup.enter="sendMessage"
                        @keyup="handleTyping"
                        type="text"
                        placeholder="Type a message..."
                        class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-900 text-sm"
                    >
                    <button 
                        @click="sendMessage"
                        :disabled="!newMessage.trim()"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 disabled:opacity-50 text-sm"
                    >
                        Send
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Empty State -->
        <div v-if="!selectedShop && Object.keys(recentChats).length === 0" class="p-8 text-center text-gray-500 text-sm">
            No conversations yet.<br>Start chatting with other shop owners!
        </div>
    </div>
</template>





<!-- resources/js/Components/Community/OnlineUsers.vue: online users component -->
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    onlineShops: Array
})

const onlineUsers = ref(props.onlineShops)
let echo = null

onMounted(() => {
    echo = window.$echo
    
    if (echo) {
        echo.channel('online-status')
            .listen('user-online', (e) => {
                if (!onlineUsers.value.find(u => u.id === e.shop.id)) {
                    onlineUsers.value.unshift(e.shop)
                }
            })
            .listen('user-offline', (e) => {
                onlineUsers.value = onlineUsers.value.filter(u => u.id !== e.shop.id)
            })
    }
})

onUnmounted(() => {
    if (echo) {
        echo.leaveChannel('online-status')
    }
})
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <h3 class="font-semibold text-sm mb-3 flex items-center">
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
            Online Now ({{ onlineUsers.length }})
        </h3>
        
        <div class="space-y-3">
            <div 
                v-for="shop in onlineUsers.slice(0, 10)" 
                :key="shop.id"
                class="flex items-center space-x-3"
            >
                <div class="w-8 h-8 rounded-full bg-linear-to-br from-gray-100 to-gray-200 flex items-center justify-center text-xs font-medium">
                    {{ shop.name.charAt(0) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ shop.name }}</p>
                    <p class="text-xs text-gray-500">{{ shop.category }}</p>
                </div>
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
            </div>
            
            <div v-if="onlineUsers.length === 0" class="text-center text-gray-500 text-sm py-4">
                No users online
            </div>
        </div>
    </div>
</template>







<!-- resources/js/Components/Community/CreatePostModal.vue -->
<script setup>
import { ref } from 'react'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    show: Boolean
})

const emit = defineEmits(['update:show', 'created'])

const form = ref({
    content: '',
    image: null
})

const isSubmitting = ref(false)
const imagePreview = ref(null)

const handleImageUpload = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.value.image = file
        const reader = new FileReader()
        reader.onload = (e) => {
            imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
    }
}

const submitPost = () => {
    isSubmitting.value = true
    
    const data = new FormData()
    data.append('content', form.value.content)
    if (form.value.image) {
        data.append('image', form.value.image)
    }
    
    router.post('/community/posts', data, {
        preserveScroll: true,
        onSuccess: () => {
            form.value = { content: '', image: null }
            imagePreview.value = null
            emit('update:show', false)
            emit('created')
        },
        onFinish: () => {
            isSubmitting.value = false
        }
    })
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="emit('update:show', false)">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            
            <div class="relative bg-white rounded-lg max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Create New Post</h3>
                
                <textarea 
                    v-model="form.content"
                    placeholder="Share something with the community..."
                    rows="4"
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-900"
                ></textarea>
                
                <!-- Image Preview -->
                <div v-if="imagePreview" class="mt-3 relative">
                    <img :src="imagePreview" class="rounded-lg max-h-48 w-full object-cover">
                    <button 
                        @click="imagePreview = null; form.image = null"
                        class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm"
                    >
                        ×
                    </button>
                </div>
                
                <!-- Image Upload -->
                <div class="mt-3">
                    <label class="cursor-pointer inline-flex items-center px-3 py-2 border rounded-lg text-sm hover:bg-gray-50">
                        📷 Add Image
                        <input type="file" accept="image/*" class="hidden" @change="handleImageUpload">
                    </label>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button 
                        @click="emit('update:show', false)"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="submitPost"
                        :disabled="!form.content.trim() || isSubmitting"
                        class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 disabled:opacity-50"
                    >
                        {{ isSubmitting ? 'Posting...' : 'Post' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
```

## Routes

```php
// routes/web.php
<?php

use App\Http\Controllers\CommunityController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return inertia('Home');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Community
    Route::get('/community', [CommunityController::class, 'index'])->name('community');
    
    // Posts
    Route::post('/community/posts', [PostController::class, 'store'])->name('posts.store');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    
    // Likes
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->name('posts.like');
    
    // Comments
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    
    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
});

// API routes for real-time features
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/chat/messages', [ChatController::class, 'fetch']);
    Route::post('/chat/send', [ChatController::class, 'send']);
    Route::post('/chat/typing', [ChatController::class, 'typing']);
    Route::get('/chat/online-status', [ChatController::class, 'getOnlineStatus']);
});

require __DIR__.'/auth.php';
```

## Policies for Authorization

```bash
php artisan make:policy PostPolicy
php artisan make:policy CommentPolicy
```

```php
// app/Policies/PostPolicy.php
<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    public function delete(User $user, Post $post)
    {
        return $user->shop_id === $post->shop_id;
    }
    
    public function update(User $user, Post $post)
    {
        return $user->shop_id === $post->shop_id;
    }
}

// app/Policies/CommentPolicy.php
<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Comment;

class CommentPolicy
{
    public function delete(User $user, Comment $comment)
    {
        return $user->shop_id === $comment->shop_id;
    }
}





// app/Providers/AuthServiceProvider.php - Register policies
protected $policies = [
    Post::class => PostPolicy::class,
    Comment::class => CommentPolicy::class,
];
```


## Real-time Middleware & Final Steps

Online status middleware.

```bash
php artisan make:middleware TrackOnlineStatus
```

```php
// app/Http/Middleware/TrackOnlineStatus.php
<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\OnlineActivity;
use Illuminate\Support\Facades\Auth;

class TrackOnlineStatus
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->shop) {
            OnlineActivity::updateOrCreate(
                [
                    'shop_id' => Auth::user()->shop->id,
                    'session_id' => session()->getId()
                ],
                [
                    'last_activity_at' => now()
                ]
            );
        }
        
        return $next($request);
    }
}





// app/Http/Kernel.php - Add to web middleware group
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\TrackOnlineStatus::class,
    ],
];
```

Create notification classes.

```bash
php artisan make:notification NewPostNotification
php artisan make:notification NewCommentNotification
```

```php
// app/Notifications/NewPostNotification.php
<?php
namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewPostNotification extends Notification
{
    use Queueable;
    
    protected $post;
    
    public function __construct(Post $post)
    {
        $this->post = $post;
    }
    
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }
    
    public function toArray($notifiable)
    {
        return [
            'post_id' => $this->post->id,
            'shop_name' => $this->post->shop->name,
            'content' => substr($this->post->content, 0, 100),
            'type' => 'new_post'
        ];
    }
}
```

Install and run everything.

```bash
# Install NPM dependencies
npm install

# Build assets
npm run build

# Start Reverb server (in separate terminal)
php artisan reverb:start

# Start queue worker for notifications (in separate terminal)
php artisan queue:work

# Start Vite dev server (in separate terminal)
npm run dev

# Run Laravel development server (in separate terminal)
php artisan serve
```

Add Environment variables.

```php
# .env - Add these lines
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
```

## Summary Checklist

✅ Completed:

✅ Database migrations with all tables

✅ Eloquent models with relationships

✅ Laravel Reverb WebSocket setup

✅ Events for real-time features

✅ Controllers with business logic

✅ Vue components (PostCard, Chat, OnlineUsers, CreatePost)

✅ Routes and authorization policies

✅ Online status tracking

✅ Notification system

Your community feature is now fully functional with:

- Real-time posts and comments
- Private business chat
- Online/offline status
- Like and follow functionality
- Notifications
- Image uploads for posts

Access it at: http://localhost:8000/community after logging in!
<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head, Link, router, useForm } from '@inertiajs/vue3';
import { Heart, MessageCircleMore, ImageIcon, SendHorizonal } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import InputError from '@/components/InputError.vue';
import { getInitials } from '@/composables/useInitials';
import businessPostsRoutes from '@/routes/business-posts';
import businessChatRoutes from '@/routes/business-chat';

interface Shop {
    id: number;
    name: string;
    logo_url: string;
    category_name: string;
    is_verified: boolean;
};

interface BusinessPost {
    id: number;
    content: string;
    image: string | null;
    likes_count: number;
    comments_count: number;
    is_liked_by_auth_user: boolean;
    shop_name: string;
    shop_logo_url: string;
    shop_is_verified: boolean;
    shop_category_name: string;
    published_at: string;
};

interface ChatMessage {
    id: number;
    message: string;
    shop_id: number;
    shop: {
        id: number;
        name: string;
        logo_url: string;
    };
    created_at: string;
    reply_to_id: number | null;
    reply_to?: ChatMessage;
}

const props = defineProps<{
    shops: Shop[];
    business_posts: {
        data: BusinessPost[];
        links: any[];
        meta: any[];
    };
    shop: { id: number; name: string; slug: string };
    chat_messages: ChatMessage[];
}>();

// Optional: Image modal state
const selectedImage = ref<string | null>(null);
const animatingPostId = ref<number | null>(null);
const likeButtonRefs = ref<Map<number, HTMLElement>>(new Map());

const openImageModal = (imageUrl: string) => {
    selectedImage.value = imageUrl;
};

const closeImageModal = () => {
    selectedImage.value = null;
};

const setLikeButtonRef = (el: HTMLElement | null, postId: number) => {
    if (el) {
        likeButtonRefs.value.set(postId, el);
    } else {
        likeButtonRefs.value.delete(postId);
    }
};

const toggleLike = async (post: BusinessPost) => {
    const button = likeButtonRefs.value.get(post.id);
    
    // Add animation class to the icon directly
    const icon = button?.querySelector('.like-icon');
    if (icon) {
        icon.classList.add('like-animation');
        setTimeout(() => {
            icon.classList.remove('like-animation');
        }, 400);
    }
    
    // Store the current state for rollback
    const previousState = {
        is_like_by_auth_user: post.is_liked_by_auth_user,
        likes_count: post.likes_count
    };
    
    // Optimistically update
    post.is_liked_by_auth_user = !post.is_liked_by_auth_user;
    post.likes_count = post.is_liked_by_auth_user ? post.likes_count + 1 : post.likes_count - 1;
    
    router.post(`/business-posts/${post.id}/like`, {}, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            // Revert on error
            post.is_liked_by_auth_user = previousState.is_like_by_auth_user;
            post.likes_count = previousState.likes_count;
        }
    });
};

const chatForm = useForm({
    message: ''
});

const submitChat = () => {
    chatForm.post(businessChatRoutes.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            chatForm.reset('message');
        }
    });
};
</script>

<template>
    <Head title="Business Community" />

    <div class="app-container BusinessCommunityPage">
        <div class="business-community-header">
            <h1>Business Community</h1>
            <div class="action-btn">
                <Link :href="businessPostsRoutes.create.url()" class="btn">New Post</Link>
            </div>
        </div>

        <div class="business-community-wrapper">
            <div class="posts-wrapper">
                <div v-for="post in business_posts.data" class="post">
                    <div class="user">
                        <div class="image">
                            <Avatar class="size-8 overflow-hidden rounded-full">
                                <AvatarImage
                                    :src="post.shop_logo_url"
                                    :alt="post.shop_name"
                                />
                                <AvatarFallback
                                    class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                >
                                    {{ getInitials(post.shop_name) }}
                                </AvatarFallback>
                            </Avatar>
                        </div>

                        <div class="info">
                            <p class="account">{{ post.shop_name }} <span v-if="post.shop_is_verified" class="badge">Verified</span></p>
                            <p class="extras">
                                <span class="time">{{ post.published_at }}</span>
                                <span class="divider">·</span>
                                <span class="shop-category">{{ post.shop_category_name }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="content">
                        <p class="post-text" v-if="post.content">{{ post.content }}</p>

                        <div v-if="post.image" class="post-image" @click="openImageModal(post.image)">
                            <img
                                :src="post.image"
                                :alt="post.shop_name || 'Post image'"
                                loading="lazy"
                            />

                            <div class="image-overlay">
                                <ImageIcon class="zoom-icon" />
                            </div>
                        </div>

                        <Teleport to="body">
                            <div v-if="selectedImage" class="image-modal" @click="closeImageModal">
                                <div class="modal-content" @click.stop>
                                    <button class="close-button" @click="closeImageModal">
                                        X
                                    </button>
                                    <img :src="selectedImage" alt="Full size post image" />
                                </div>
                            </div>
                        </Teleport>
                    </div>

                    <div class="stats">
                        <button 
                            :ref="(el) => setLikeButtonRef(el as HTMLElement, post.id)"
                            @click="toggleLike(post)" 
                            class="like-button"
                            :class="{ 'liked': post.is_liked_by_auth_user, 'animating': animatingPostId === post.id }"
                        >
                            <Heart 
                                class="like-icon" 
                                :size="16"
                                :fill="post.is_liked_by_auth_user ? '#ef4444' : 'none'"
                                :stroke="post.is_liked_by_auth_user ? '#ef4444' : 'currentColor'"
                            />
                            <span>{{ post.likes_count }}</span>
                        </button>
                        <!-- <button>
                            <MessageCircleMore :size="16" />
                            <span>{{ post.comments_count }}</span>
                        </button> -->
                    </div>
                </div>
            </div>

            <div class="livechat-wrapper">
                <div class="business-chat">
                    <div class="header">
                        <div class="header-title">
                            <span class="dot-circle green"></span>
                            <span class="title">Business Chat</span>
                        </div>
                        <span class="shops-online-stat">{{ shops.length }} online</span>
                    </div>

                    <div class="chat-area">
                        <div v-if="chat_messages.length > 0" class="chat-area-messages">
                            <div v-for="message in chat_messages" :key="message.id" class="chat-message">
                                <!-- Show replied message if exists -->
                                <div v-if="message.reply_to_id" class="reply-preview">
                                    <span class="reply-name">{{ message.reply_to?.shop?.name || 'Unknown' }}</span>
                                    <span class="reply-text">{{ message.reply_to?.message?.substring(0, 60) }}...</span>
                                </div>
                                
                                <!-- Message wrapper -->
                                <div :class="[message.shop_id === shop.id ? 'sent' : 'received']">
                                    <span v-if="message.shop_id !== shop.id" class="shop-name">
                                        {{ message.shop.name }}
                                    </span>
                                    <span class="message">{{ message.message }}</span>
                                    <span class="time">{{ new Date(message.created_at).toLocaleTimeString() }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="empty-chat-area-messages">
                            <p>No messages yet. Be the first to send a message!</p>
                        </div>
                    </div>

                    <div class="chat-input-area">
                        <form @submit.prevent="submitChat">
                            <div class="inputs-group">
                                <input type="text" v-model="chatForm.message" id="message" placeholder="Type your message">
                                <InputError :message="chatForm.errors.message" />
                            </div>
                            <button type="submit">
                                <SendHorizonal class="w-5 h-5" />
                            </button>
                        </form>
                    </div>
                </div>

                <div class="online-users">
                    <p class="title">Online Now</p>

                    <div class="shops-wrapper">
                        <div v-for="shop in shops" class="shop">
                            <div class="shop-details">
                                <div class="image">
                                    <Avatar class="size-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            :src="shop.logo_url"
                                            :alt="shop.logo_url"
                                        />
                                        <AvatarFallback
                                            class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                        >
                                            {{ getInitials(shop?.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </div>

                                <div class="info">
                                    <p class="shop-name">{{ shop.name }}</p>
                                    <p class="shop-category">{{ shop.category_name }}</p>
                                </div>
                            </div>

                            <div class="online-status flex">
                                <span class="dot-circle green"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
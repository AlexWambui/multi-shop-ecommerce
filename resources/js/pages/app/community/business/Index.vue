<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { HeartIcon, MessageCircleMore } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';

interface Shop {
    id: number;
    name: string;
    logo_url: string;
    category_name: string;
    is_verified: boolean;
};

const props = defineProps<{
    shops: Shop[];
}>();
</script>

<template>
    <Head title="Business Community" />

    <div class="app-container BusinessCommunityPage">
        <div class="business-community-header">
            <h1>Business Community</h1>
            <div class="action-btn">
                <Link href="/business-community/create" class="btn">New Post</Link>
            </div>
        </div>

        <div class="business-community-wrapper">
            <div class="posts-wrapper">
                <div v-for="shop in shops" class="post">
                    <div class="user">
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
                            <p class="account">{{ shop.name }} <span v-if="shop.is_verified" class="badge">Verified</span></p>
                            <p class="extras">
                                <span class="time">2 hours ago</span>
                                <span class="divider">·</span>
                                <span class="shop-category">Beaty & Wellness</span>
                            </p>
                        </div>
                    </div>

                    <div class="content">
                        <p>Excited to announce our new Moringa Glow Collection launches this Friday! We've been formulating for 6 months. Early access for Sokohub community members — drop a 🌿 below and I'll DM you the link before it goes live.</p>
                    </div>

                    <div class="stats">
                        <p>
                            <span class="icon likes">
                                <HeartIcon />
                            </span>
                            <span>47 Likes</span>
                        </p>
                        <p>
                            <span class="icon comments">
                                <MessageCircleMore />
                            </span>
                            <span>47 Comments</span>
                        </p>
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
                        <span class="shops-online-stat">24 online</span>
                    </div>

                    <div class="chat-area">
                        <p class="received">
                            <span class="shop-name">Mama's Pantry</span>
                            <span class="message">Anyone know a reliable courier in Mombasa?</span>
                        </p>
                        <p class="received">
                            <span class="shop-name">Amani Botanics</span>
                            <span class="message">Try Sendy! They've been great for us 🙌</span>
                        </p>
                        <p class="sent">
                            <span class="message">Yes Sendy is solid. G4S also works for bulk.</span>
                        </p>
                        <p class="received">
                            <span class="shop-name">Tech Nairobi</span>
                            <span class="message">Agreed on Sendy. Their API is handy too.</span>
                        </p>
                    </div>

                    <div class="chat-input-area">
                        <form action="">
                            <input type="text" name="message" id="message" placeholder="Type a message">
                            <button type="submit">Send</button>
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

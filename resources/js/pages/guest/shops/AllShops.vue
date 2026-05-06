<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Pagination from '@/components/custom/Pagination.vue';
import type { Shop } from '@/types/shop';

// Define props
const props = defineProps<{
    shops: {
        data: Shop[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: any[];
        }
    };
    filters?: {
        search: string;
    };
}>();

// Search functionality
const search = ref(props.filters?.search || '');

const handleSearch = () => {
    router.get('/all-shops', { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearSearch = () => {
    search.value = '';
    router.get('/all-shops', { search: '' }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <GuestLayout>
        <div class="main_container">
            <section class="Hero flex items-center justify-between">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold">Discover Shops</h1>
                    <p class="mt-2">Browse through our collection of shops</p>
                </div>

                <div class="mb-8">
                    <div class="max-w-md">
                        <label for="search" class="sr-only">Search shops</label>
                        <div class="relative">
                            <input
                                id="search"
                                v-model="search"
                                type="text"
                                placeholder="Search by shop name, contact email, or contact phone..."
                                @keyup.enter="handleSearch"
                                class="w-full rounded-sm border border-border shadow-sm focus:border-gray-500 focus:ring-gray-500 py-1 px-4 pr-10"
                            />
                            <!-- Clear button (X) -->
                            <button
                                v-if="search.length > 0"
                                @click="clearSearch"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                type="button"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="shops.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="shop in shops.data" 
                    :key="shop.id"
                    class="shop-card"
                    @click="$inertia.visit(`/shop-details/${shop.slug}`)"
                >
                    <div class="shop-card-images">
                        <div class="shop-card-cover">
                            <img :src="shop.cover_url" alt="Shop cover Image">
                        </div>

                        <div class="shop-card-logo">
                            <img :src="shop.logo_url" alt="Shop logo">
                        </div>
                    </div>

                    <div class="shop-card-body">
                        <h3 class="shop-card-name">{{ shop.name }}</h3>
                        <p class="shop-card-category">{{ shop.category_name }}</p>
                        <div class="shop-card-meta">
                            <!-- TODO: Show actual shop stats -->
                            <div class="shop-rating">
                                <span class="star">★</span> 
                                <span class="rating">0</span>
                                <span class="reviews">(0 reviews)</span>
                            </div>
                            <span class="badge">{{ shop.is_active ? 'Open' : 'Closed' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- No Results -->
            <div v-else class="text-center py-12">
                <p class="text-gray-500 text-lg">No shops found</p>
                <p v-if="filters?.search" class="text-gray-400 mt-2">
                    No shops matching "{{ filters.search }}" were found.
                </p>
            </div>

            <!-- Pagination -->
            <Pagination :meta="shops.meta" />
        </div>
    </GuestLayout>
</template>
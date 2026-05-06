<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Star } from 'lucide-vue-next';
import Button from '@/components/ui/button/Button.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import ProductCard from '@/pages/guest/components/ProductCard.vue';
import type { Shop } from '@/types/shop';
import type { Product } from '@/types/product';
import { useCartStore } from '@/store/cart';

interface ShopStats {
    total_products: number;
    total_sales: number;
    total_reviews: number;
    average_rating: number;
    response_rate: number;
}

const props = defineProps<{
    shop: {
        data: Shop;
    };
    stats: ShopStats;
    products: {
        data: Product[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: any[];
        }
    };
}>();

const activeTab = ref('products');

const tabs = [
    { id: 'products', label: 'All Products' },
    { id: 'on_offer', label: 'On Offer' },
    { id: 'about', label: 'About' },
    { id: 'reviews', label: 'Reviews' },
];

const cartStore = useCartStore();
onMounted(() => {
    cartStore.fetchCart();
});
</script>

<template>
    <Head :title="shop.data.name" />

    <GuestLayout>
        <div class="main_container ShopDetailsPage">
            <section class="back-button">
                <Link href="/">
                    <Button variant="outline">&larr; Back to Discover</Button>
                </Link>
            </section>

            <section class="Hero">
                <div class="hero-wrapper">
                    <div class="icon-text">
                        <div class="icon">
                            <img :src="shop.data.logo_url" :alt="shop.data.name" />
                        </div>
                        <div class="text">
                            <div class="text-wrapper">
                                <h2 class="name">{{ shop.data.name }}</h2>
                                <div class="badges">
                                    <span>{{ shop.data.is_active ? 'Open' : 'Closed' }}</span>
                                    <span>{{ shop.data.category_name }}</span>
                                </div>
                                <p class="description">{{ shop.data.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="stats">
                        <div class="stat">
                            <div class="number">{{ stats.total_products }}</div>
                            <div class="text">Products</div>
                        </div>
                        <div class="stat">
                            <div class="number">{{ stats.average_rating }}★</div>
                            <div class="text">Rating</div>
                        </div>
                        <div class="stat">
                            <div class="number">{{ stats.total_reviews }}</div>
                            <div class="text">Reviews</div>
                        </div>
                        <div class="stat">
                            <div class="number">{{ stats.total_sales }}</div>
                            <div class="text">Sales</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="TabsContent">
                <div class="tabs">
                    <div class="tabs-wrapper">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'pb-3 text-sm font-medium transition-colors',
                                activeTab === tab.id
                                    ? 'border-b-2 border-gray-900 text-gray-900 dark:text-gray-200'
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            {{ tab.label }}
                            <!-- Show count badge on On Offer tab if there are deals -->
                            <!-- <span
                                v-if="tab.id === 'on_offer' && discountedProducts.length > 0"
                                class="ml-1.5 px-1.5 py-0.5 text-xs bg-red-100 text-red-600 rounded-full"
                            >
                                {{ discountedProducts.length }}
                            </span> -->
                        </button>
                    </div>
                </div>

                <!-- All Products tab -->
                <div v-if="activeTab === 'products'" class="products-tab">
                    <div v-if="products.data.length > 0" class="products-wrapper">
                        <ProductCard
                            v-for="product in products.data"
                            :key="product.id"
                            :product="product"
                            :show-stock-indicator="true"
                            :show-add-to-cart="true"
                        />
                    </div>
                    <div v-if="products.meta?.links?.length > 3" class="table-pagination">
                        <Link 
                            v-for="link in products.meta.links" 
                            :key="link.label"
                            :href="link.url || '#'"
                            v-html="link.label"
                            preserve-scroll
                            class="pagination-link"
                            :class="{ 'bg-blue-600 text-white': link.active }"
                        />
                    </div>
                </div>

                <!-- On Offer tab — only discounted products -->
                <!-- <div v-if="activeTab === 'on_offer'" class="products-tab">
                    <div v-if="discountedProducts.length > 0" class="products-wrapper">
                        <ProductCard
                            v-for="product in discountedProducts"
                            :key="product.id"
                            :product="product"
                            :show-stock-indicator="true"
                            :show-add-to-cart="true"
                        />
                    </div>
                    <div v-else class="py-12 text-center text-gray-400 text-sm">
                        No active offers in this shop right now.
                    </div>
                </div> -->

                <!-- About tab -->
                <div v-if="activeTab === 'about'" class="about-tab">
                    <div class="about-tab-wrapper">
                        <h2 class="name">{{ shop.data.name }}</h2>
                        <p class="description">{{ shop.data.description || 'No description provided.' }}</p>
                        <div class="info">
                            <h3 class="title">Shop Information</h3>
                            <dl class="details">
                                <div>
                                    <dt>Shop Owner</dt>
                                    <dd>{{ shop.data.owner_name }}</dd>
                                </div>
                                <div>
                                    <dt>Member Since</dt>
                                    <dd>{{ shop.data.owner_joined_at }}</dd>
                                </div>
                                <div>
                                    <dt>Category</dt>
                                    <dd>{{ shop.data.category_name }}</dd>
                                </div>
                                <div>
                                    <dt>Total Products</dt>
                                    <dd>{{ stats.total_products }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Reviews tab -->
                <div v-if="activeTab === 'reviews'" class="reviews-tab">
                    <div class="reviews-wrapper">
                        <Star class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <h3 class="title">Reviews Coming Soon</h3>
                        <p class="description">Customer reviews will appear here once available.</p>
                    </div>
                </div>
            </section>
        </div>
    </GuestLayout>
</template>
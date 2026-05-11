<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import shopsRoutes from '@/routes/my-shops';
import shopProducts from '@/routes/my-shops/products';
import shopDiscounts from '@/routes/my-shops/discounts';
import ShopNav from './components/ShopNav.vue';

interface Shop {
    id: number;
    name: string;
    slug: string;
};

interface Stat {
    total_products: number;
};

interface Props {
    shop: Shop;
    stats: Stat;
}

const props = defineProps<Props>();
</script>

<template>
    <ShopNav :shop-id="shop.id" :shop-slug="shop.slug" current-page="shop" />
    
    <div class="app-container MyShopShowPage">
        <h1>{{ shop.name }}</h1>

        <div class="Stats">
            <div class="stats-wrapper">
                <div class="stat">
                    <div class="label">Products</div>
                    <div class="number">{{ stats.total_products }}</div>
                    <!-- TODO: Show actual product with low stock -->
                    <div class="extras">&darr; 3 low stock</div>
                </div>
            </div>
        </div>
    </div>
</template>
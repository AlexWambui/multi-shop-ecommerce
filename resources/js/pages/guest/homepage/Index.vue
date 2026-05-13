<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import HeroSection from './components/Hero.vue';
import FeaturedShops from './components/FeaturedShops.vue';
import HotDeals from './components/Deals/HotDeals.vue';
import type { Shop } from '@/types/shop';
import type { Product } from '@/types/product';

interface HeroStats {
    total_active_shops: number;
    total_active_products: number;
};

const props = defineProps<{
    stats: HeroStats;
    featured_shops: Shop[];
    hot_deals: {
        data: Product[]
    };
}>();
</script>

<template>
    <GuestLayout>
        <div class="HomePage">
            <HeroSection :stats="stats" />
            <FeaturedShops :featured_shops="featured_shops" />
            <HotDeals :hot_deals="hot_deals" />
        </div>
    </GuestLayout>
</template>
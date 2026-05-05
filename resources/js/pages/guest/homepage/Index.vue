<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import HeroSection from './components/Hero.vue';
import FeaturedShops from './components/FeaturedShops.vue';
import type { Shop } from '@/types/shop';

interface HeroStats {
    total_active_shops: number;
    total_active_products: number;
};

const props = defineProps<{
    featured_shops: Shop[];
    stats: HeroStats;
}>();
</script>

<template>
    <GuestLayout>
        <div class="HomePage">
            <HeroSection :stats="stats" />
            <FeaturedShops :featured_shops="featured_shops" />
        </div>
    </GuestLayout>
</template>
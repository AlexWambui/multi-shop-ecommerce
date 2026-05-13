<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Hero from './components/Hero.vue';
import CategoryPills from './components/CategoryPills.vue';
import FlashSales from './components/FlashSales.vue';
import ClearanceSales from './components/ClearanceSales.vue';
import type { Product } from '@/types/product';

const props = defineProps<{
    product_categories: any[];
    flash_sales: {
        data: Product[];
    };
    clearance_sales: {
        data: Product[];
    };
}>();
</script>

<template>
    <Head title="Hot Deals" />
    <GuestLayout>
        <div class="DealsPage">
            <Hero />
            <CategoryPills :product_categories="product_categories" />
            <FlashSales :flash_sales="flash_sales" />
            <ClearanceSales :clearance_sales="clearance_sales" />
        </div>
    </GuestLayout>
</template>
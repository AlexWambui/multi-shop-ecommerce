<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import EmptyDeals from '@/pages/guest/components/EmptyDeals.vue';
import ProductCard from '@/pages/guest/components/ProductCard.vue';
import type { Product } from '@/types/product';

const props = defineProps<{
    clearance_sales: {
        data: Product[];
    };
}>();
</script>

<template>
    <section class="ClearanceSales">
        <div class="section-header">
            <div class="section-title">Clearance</div>
            <Link href="/deals-page" class="section-link">{{ clearance_sales.data.length || 0 }} active</Link>
        </div>

        <div v-if="clearance_sales && clearance_sales.data.length > 0" class="clearancesales-wrapper">
            <ProductCard
                v-for="product in clearance_sales.data"
                :key="product.id"
                :product="product"
                :show-stock-indicator="true"
                :show-add-to-cart="true"
            />
        </div>

        <div v-else class="deals-empty-state">
            <EmptyDeals type="clearance" />
        </div>
    </section>
</template>
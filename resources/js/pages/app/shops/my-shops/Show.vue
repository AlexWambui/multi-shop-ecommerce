<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import shopsRoutes from '@/routes/my-shops';
import shopProducts from '@/routes/my-shops/products';
import shopDiscounts from '@/routes/my-shops/discounts';

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

const breadcrumbs = computed(() => [
    { title: 'Shops', href: shopsRoutes.index().url },
    { title: 'Products', href: shopProducts.index(props.shop.slug) },
    { title: 'Discounts', href: shopDiscounts.index(props.shop.slug) },
    { title: 'Summary', description: 'Shop details' }
]);

const page = usePage();
page.props.breadcrumbs = breadcrumbs.value;
</script>

<template>
    <div class="app-container MyShopShowPage">
        <h1>{{ props.shop.name }}</h1>

        <div class="Stats">
            <div class="stats-wrapper">
                <div class="stat">
                    <div class="label">Products</div>
                    <div class="number">{{ props.stats.total_products }}</div>
                    <!-- TODO: Show actual product with low stock -->
                    <div class="extras">&darr; 3 low stock</div>
                </div>
            </div>
        </div>
    </div>
</template>
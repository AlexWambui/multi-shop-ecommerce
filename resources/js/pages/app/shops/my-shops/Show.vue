<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import shopsRoutes from '@/routes/my-shops';
import shopProducts from '@/routes/my-shops/products';
import { DefineStoreOptionsInPlugin } from 'pinia';

interface Shop {
    id: number;
    name: string;
    slug: string;
};

interface Props {
    shop: Shop
}

const props = defineProps<Props>();

const breadcrumbs = computed(() => [
    { title: 'Shops', href: shopsRoutes.index().url },
    { title: 'Products', href: shopProducts.index(props.shop.slug) },
    { title: 'Shop Details', description: 'Shop Details' }
]);

const page = usePage();
page.props.breadcrumbs = breadcrumbs.value;
</script>

<template>
    <div class="app-container">
        <h1>{{ props.shop.name }}</h1>
    </div>
</template>
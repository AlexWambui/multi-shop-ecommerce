<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import myShopsRoutes from '@/routes/my-shops';
import myShopOrdersRoutes from '@/routes/my-shops/orders';
import myShopDiscountsRoutes from '@/routes/my-shops/discounts';
import myShopProductsRoutes from '@/routes/my-shops/products';
import myShopInventoryRoutes from '@/routes/my-shops/inventory';

interface Props {
    shopId: number;
    shopSlug: string;
    currentPage: 'shop' | 'orders' | 'products' | 'discounts' | 'inventory';
}

const props = defineProps<Props>();

const links = {
    shop: { name: 'Shop', href: myShopsRoutes.show(props.shopId).url },
    orders: { name: 'Orders', href: myShopOrdersRoutes.index(props.shopSlug).url },
    products: { name: 'Products', href: myShopProductsRoutes.index(props.shopSlug) },
    discounts: { name: 'Discounts', href: myShopDiscountsRoutes.index(props.shopSlug) },
    inventory: { name: 'Inventory', href: myShopInventoryRoutes.index(props.shopSlug) },
};

const breadcrumbItems = computed(() => [
    { name: 'Shop', href: myShopsRoutes.show(props.shopId).url, key: 'shop' },
    { name: 'Orders', href: myShopOrdersRoutes.index(props.shopSlug).url, key: 'orders' },
    { name: 'Products', href: myShopProductsRoutes.index(props.shopSlug), key: 'products' },
    { name: 'Discounts', href: myShopDiscountsRoutes.index(props.shopSlug), key: 'discounts' },
    { name: 'Inventory', href: myShopInventoryRoutes.index(props.shopSlug), key: 'inventory' },
]);
</script>

<template>
    <div class="shop-nav py-4 px-4 lg:px-16 w-full border-b border-sidebar-border/80" aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 text-sm">
            <li v-for="(item, idx) in breadcrumbItems" :key="item.key" class="flex items-center gap-2">
                <Link
                    :href="item.href"
                    class="transition-colors"
                    :class="{
                        'text-foreground font-semibold pointer-events-none': currentPage === item.key,
                        'text-muted-foreground hover:text-foreground' : currentPage !== item.key
                    }"
                >
                    {{ item.name }}
                </Link>
                <span v-if="idx < breadcrumbItems.length - 1" class="text-muted-foreground">/</span>
            </li>
        </ol>
    </div>
</template>

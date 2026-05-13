<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Link, router, usePage } from '@inertiajs/vue3';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import Pagination from '@/components/custom/Pagination.vue';
import ShopNav from '../components/ShopNav.vue';
import type { Product } from '@/types/product';
import myShopProductsRoutes from '@/routes/my-shops/products';
import { usePriceFormatter } from '@/composables/usePriceFormatter';

const { formatPrice } = usePriceFormatter();

interface Shop {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    shop: Shop;
    products: {
        data: Product[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: any[];
        };
    };
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');
const status_filter = ref(props.filters?.status || '');

const debouncedSearch = useDebounceFn(() => {
    router.get(myShopProductsRoutes.index(props.shop.slug).url, {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, () => {
    debouncedSearch();
});

const getDisplayRange = computed(() => {
    const { current_page, per_page, total } = props.products.meta;
    const start = (current_page - 1) * per_page + 1;
    const end = Math.min(current_page * per_page, total);
    return { start, end, total };
});

const hasActiveFilters = computed(() => 
    !!(search.value || status_filter.value)
);
</script>

<template>
    <ShopNav :shop-id="shop.id" :shop-slug="shop.slug" current-page="products" />
    
    <div class="app-container">
        <div class="header">
            <div class="info">
                <h1 class="title">{{ props.shop.name }} - Products</h1>
            </div>

            <div class="search">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or slug..."
                />
            </div>

            <Link :href="myShopProductsRoutes.create(props.shop.slug).url">
                <Button>New Product</Button>
            </Link>
        </div>

        <div class="table-wrapper">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="id">#</TableHead>
                        <TableHead>Product</TableHead>
                        <TableHead>SKU</TableHead>
                        <TableHead>Price (Ksh)</TableHead>
                        <TableHead>Stock</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="(product, index) in products.data" :key="product.id">
                        <TableCell class="id">{{ (products.meta.current_page - 1) * products.meta.per_page + index + 1 }}</TableCell>
                        <TableCell>{{ product.name }}</TableCell>
                        <TableCell>{{ product.sku }}</TableCell>
                        <TableCell>
                            <div v-if="product.has_discount" class="flex items-center gap-2">
                                <span>{{ formatPrice(product.discounted_price) }}</span>
                                <span class="text-muted-foreground line-through">{{ formatPrice(product.price) }}</span>
                                <span class="text-xs text-green-500 italic">Save {{ formatPrice(product.discount_display.saved_amount) }}</span>
                            </div>
                            <span v-else>
                                {{ formatPrice(product.price) }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <span :class="{ 'font-bold text-red-600': product.current_stock === 0 }">
                                {{ product.track_inventory ? product.current_stock : '∞' }}
                            </span>
                        </TableCell>
                        <TableCell>{{ product.category_name }}</TableCell>
                        <TableCell class="actions">
                            <div class="actions-wrapper">
                                <Link :href="myShopProductsRoutes.edit({ shop: props.shop.slug, product: product.id }).url" class="action edit">
                                    Edit
                                </Link>
                                <span class="divider">|</span>
                                <DeleteConfirmationDialog 
                                    :url="myShopProductsRoutes.destroy({ shop: props.shop.slug, product: product.id }).url" 
                                    title="Delete Product?" 
                                    description="This product will be deleted permanently!" 
                                    confirm-text="Delete Product"
                                >
                                    <template #trigger>
                                        <button class="action delete">
                                            Delete
                                        </button>
                                    </template>
                                </DeleteConfirmationDialog>
                            </div>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="products.data.length === 0">
                        <TableCell colspan="5" class="blank-table-row">
                            No products found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :meta="products.meta" />

        <div class="table-results-summary">
            <p>
                Showing {{ getDisplayRange.start }} to {{ getDisplayRange.end }}
                of {{ getDisplayRange.total }} products
            </p>
            <p v-if="hasActiveFilters" class="filtered-results">
                Filtered results
            </p>
        </div>
    </div>
</template>
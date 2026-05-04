<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Link, router, usePage } from '@inertiajs/vue3';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import myShopsRoutes from '@/routes/my-shops';
import myShopProductsRoutes from '@/routes/my-shops/products';

interface Shop {
    id: number;
    name: string;
    slug: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string;
    price: number;
    category_name: string;
}

interface Props {
    products: Product[];
    shop: Shop;
    filters: {
        search?: string;
        status?: string;
    };
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: any[];
    };
}

const props = defineProps<Props>();

const breadcrumbs = computed(() => [
    { title: 'Shops', href: myShopsRoutes.show(props.shop.id).url },
    { title: 'Products', description: 'My shop products' }
]);

const page = usePage();
page.props.breadcrumbs = breadcrumbs.value;

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
    const { current_page, per_page, total } = props.pagination;
    const start = (current_page - 1) * per_page + 1;
    const end = Math.min(current_page * per_page, total);
    return { start, end, total };
});

const hasActiveFilters = computed(() => 
    !!(search.value || status_filter.value)
);
</script>

<template>
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
                        <!-- TODO: Show discount -->
                        <TableHead>Discount</TableHead>
                        <TableHead>Price</TableHead>
                        <!-- TODO: Show Stock Count -->
                        <TableHead>Stock</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="(product, index) in props.products" :key="product.id">
                        <TableCell class="id">{{ (props.pagination.current_page - 1) * props.pagination.per_page + index + 1 }}</TableCell>
                        <TableCell>{{ product.name }}</TableCell>
                        <TableCell>{{ product.sku }}</TableCell>
                        <TableCell>Discount</TableCell>
                        <TableCell>{{ product.price }}</TableCell>
                        <TableCell>Stock</TableCell>
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

                    <TableRow v-if="props.products.length === 0">
                        <TableCell colspan="5" class="blank-table-row">
                            No products found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="props.pagination.links?.length > 3" class="table-pagination">
            <Link 
                v-for="link in props.pagination.links" 
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                preserve-scroll
                class="pagination-link"
                :class="{ 'bg-blue-600 text-white': link.active }"
            />
        </div>

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
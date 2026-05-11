<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Head, Link, router } from '@inertiajs/vue3';
import { Package, AlertTriangle, CheckCircle, XCircle, TrendingUp, DollarSign } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Pagination from '@/components/custom/Pagination.vue';
import ShopNav from '../components/ShopNav.vue';
import type { Product } from '@/types/product';
import myShopInventoryRoutes from '@/routes/my-shops/inventory';

const props = defineProps<{
    shop: { id: number; name: string; slug: string };
    products: { data: Product[]; meta: any };
    stats: {
        total_products: number;
        low_stock_count: number;
        out_of_stock_count: number;
        total_value: number;
    };
    filters: { search?: string; stock_status?: string };
}>();

const search = ref(props.filters.search || '');
const stockStatusFilter = ref(props.filters.stock_status || '');

const debouncedSearch = useDebounceFn(() => {
    router.get(myShopInventoryRoutes.index(props.shop.slug).url, {
        search: search.value,
        stock_status: stockStatusFilter.value
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, stockStatusFilter], () => {
    debouncedSearch();
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES'
    }).format(amount);
};

const getStockIcon = (product: Product) => {
    if (!product.track_inventory) return TrendingUp;
    if (product.current_stock <= 0) return XCircle;
    if (product.current_stock <= product.low_stock_threshold) return AlertTriangle;
    return CheckCircle;
};

const getDisplayRange = computed(() => {
    const { current_page, per_page, total } = props.products.meta;
    const start = (current_page - 1) * per_page + 1;
    const end = Math.min(current_page * per_page, total);
    return { start, end, total };
});

const hasActiveFilters = computed(() => 
    !!(search.value || stockStatusFilter.value)
);
</script>

<template>
    <Head title="Inventory Management" />

    <ShopNav :shop-id="shop.id" :shop-slug="shop.slug" current-page="inventory" />

    <div class="app-container">
        <div class="header">
            <div class="info">
                <h1 class="title">Inventory Management</h1>
                <p class="description">{{ shop.name }} - Manage stock levels for all products</p>
            </div>

            <div>
                <!-- <Link href="route('my-shops.inventory.bulk', shop.slug)">
                    <Button variant="outline">Bulk Upload CSV</Button>
                </Link> -->
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue-100">
                    <Package class="w-5 h-5 text-blue-600" />
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ stats.total_products }}</div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-orange-100">
                    <AlertTriangle class="w-5 h-5 text-orange-600" />
                </div>
                <div class="stat-info">
                    <div class="stat-value text-orange-600">{{ stats.low_stock_count }}</div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-red-100">
                    <XCircle class="w-5 h-5 text-red-600" />
                </div>
                <div class="stat-info">
                    <div class="stat-value text-red-600">{{ stats.out_of_stock_count }}</div>
                    <div class="stat-label">Out of Stock</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-green-100">
                    <DollarSign class="w-5 h-5 text-green-600" />
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ formatCurrency(stats.total_value) }}</div>
                    <div class="stat-label">Inventory Value</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <Input
                v-model="search"
                type="text"
                placeholder="Search by name or SKU..."
                class="search-input"
            />
            
            <select v-model="stockStatusFilter" class="status-filter">
                <option value="">All Status</option>
                <option value="in_stock">In Stock</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
                <option value="unlimited">Unlimited (No Tracking)</option>
            </select>
        </div>

        <!-- Products Table -->
        <div class="table-wrapper">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Product</TableHead>
                        <TableHead>SKU</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Current Stock</TableHead>
                        <TableHead>Threshold</TableHead>
                        <TableHead>Cost Price</TableHead>
                        <TableHead>Selling Price</TableHead>
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="product in products.data" :key="product.id">
                        <TableCell class="font-medium">{{ product.name }}</TableCell>
                        <TableCell class="text-sm text-gray-500">{{ product.sku || 'N/A' }}</TableCell>
                        <TableCell>
                            <div :class="product.stock_badge_class" 
                                 class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium">
                                <component :is="getStockIcon(product)" class="w-3 h-3" />
                                {{ product.stock_status }}
                            </div>
                        </TableCell>
                        <TableCell>
                            <span :class="{ 'font-bold text-red-600': product.current_stock === 0 }">
                                {{ product.track_inventory ? product.current_stock : '∞' }}
                            </span>
                        </TableCell>
                        <TableCell>{{ product.low_stock_threshold }}</TableCell>
                        <TableCell>{{ formatCurrency(product.cost_price) }}</TableCell>
                        <TableCell>{{ formatCurrency(product.price) }}</TableCell>
                        <TableCell class="actions">
                            <div class="action-buttons">
                                <Link :href="myShopInventoryRoutes.create({shop: shop.slug, product: product.id})"
                                      class="btn-sm btn-primary">
                                    + Add Stock
                                </Link>
                                <Link :href="myShopInventoryRoutes.edit({shop: shop.slug, product: product.id})"
                                      class="btn-sm btn-danger">
                                    - Remove
                                </Link>
                                <Link :href="myShopInventoryRoutes.history({shop: shop.slug, product: product.id})"
                                      class="btn-sm btn-outline">
                                    History
                                </Link>
                            </div>
                        </TableCell>
                    </TableRow>
                    
                    <TableRow v-if="products.data.length === 0">
                        <TableCell colspan="7" class="text-center py-8 text-gray-500">
                            No products found
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
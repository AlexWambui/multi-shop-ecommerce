<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Pagination from '@/components/custom/Pagination.vue';
import TableResultsSummary from '@/components/custom/Tables/ResultsSummary.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import ShopNav from '../components/ShopNav.vue';
import myShopOrdersRoutes from '@/routes/my-shops/orders';
import { usePriceFormatter } from '@/composables/usePriceFormatter';

const { formatPrice } = usePriceFormatter();

interface Order {
    id: number;
    order_number: string;
    total_amount: number;
    order_status: boolean;
    order_status_label: string;
    payment_status: boolean;
    payment_status_label: string;
    created_at: string;
};

interface Shop {
    id: number;
    uuid: string;
    name: string;
    slug: string;
};

interface Props {
    orders: {
        data: Order[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: any[];
        }
    },
    shop: Shop

    search?: string;
};

const props = defineProps<Props>();

const search = ref(props.search || '');
const debouncedSearch = useDebounceFn(() => {
    router.get(myShopOrdersRoutes.index(props.shop.uuid).url, {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);
watch([search], () => {
    debouncedSearch();
});

const hasActiveFilters = computed(() =>
    !!(search.value)
);
</script>

<template>
    <Head title="My Shop Orders" />

    <ShopNav :shop-id="shop.id" :shop-slug="shop.slug" current-page="orders" />

    <div class="app-container">
        <div class="header">
            <div class="info">
                <h1 class="title">Orders</h1>
            </div>

            <div class="search">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or slug"
                />
            </div>

            <div>
                <!-- <Link :href="myShopOrdersRoutes.create().url">
                    <Button>New Order</Button>
                </Link> -->
            </div>
        </div>

        <div class="table-wrapper">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="id">#</TableHead>
                        <TableHead>Order</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Payment</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="(order, index) in orders.data" :key="order.id">
                        <TableCell class="id">{{ (orders.meta.current_page - 1) * orders.meta.per_page + index + 1 }}</TableCell>
                        <TableCell>{{ order.order_number }}</TableCell>
                        <TableCell>{{ formatPrice(order.total_amount) }}</TableCell>
                        <TableCell :class="{ 'font-semibold text-green-600' : order.order_status_label === 'Delivered' }">{{ order.order_status_label }}</TableCell>
                        <TableCell :class="{ 'font-semibold text-green-600' : order.payment_status_label === 'Paid' }">{{ order.payment_status_label }}</TableCell>
                        <TableCell>{{ order.created_at }}</TableCell>
                        <TableCell class="actions">
                            <div class="actions-wrapper">
                                <Link :href="myShopOrdersRoutes.edit({ shop: shop.uuid, order: order.id}).url" class="action edit">
                                    <Pencil class="icon edit" />
                                </Link>

                                <span class="divider">|</span>

                                <DeleteConfirmationDialog :url="myShopOrdersRoutes.destroy({ shop: shop.uuid, order: order.id}).url" title="Delete Order?" description="This order will be deleted permanently!" confirm-text="Delete Order">
                                    <template #trigger>
                                        <button class="action delete">
                                            <Trash2 class="icon delete" />
                                        </button>
                                    </template>
                                </DeleteConfirmationDialog>
                            </div>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="orders.data.length === 0">
                        <TableCell colspan="5" class="blank-table-row">
                            No orders found!
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :meta="orders.meta" />

        <TableResultsSummary
            :meta="orders.meta"
            item-name="order"
            item-name-plural="orders"
            :show-filter-indicators="true"
            :has-active-filters="hasActiveFilters"
        />
    </div>
</template>

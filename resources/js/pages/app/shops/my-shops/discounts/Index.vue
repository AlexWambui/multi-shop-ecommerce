<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import Pagination from '@/components/custom/Pagination.vue';
import ShopNav from '../components/ShopNav.vue';
import myShopDiscountsRoutes from '@/routes/my-shops/discounts';

interface Discount {
    id: number;
    name: string;
    type_label: string;
    formatted_value: string;
    scope_label: string;
    is_active: boolean;
    is_expired: boolean;
    starts_at: string;
    expires_at: string;
    status: string;
    targets_count: number;
}

interface Props {
    discounts: {
        data: Discount[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: any[];
        };
    };
    shop: {
        id: number;
        name: string;
        slug: string;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const now = ref(new Date());

let interval: ReturnType<typeof setInterval>;

onMounted(() => {
    interval = setInterval(() => {
        now.value = new Date();
    }, 60000); // Updates every 60 seconds
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});

const discountsWithRealtimeStatus = computed(() => {
    return props.discounts.data.map(discount => {
        const startsAt = new Date(discount.starts_at);
        const expiresAt = new Date(discount.expires_at);
        const isNowActive = discount.is_active && startsAt <= now.value && expiresAt >= now.value;

        return {
            ...discount,
            is_active_now: isNowActive,
            is_expired_now: expiresAt < now.value,
            status: isNowActive ? 'Active' : (expiresAt < now.value ? 'Expired' : (startsAt > now.value ? 'Scheduled' : 'Inactive'))
        };
    });
});

const search = ref(props.filters?.search || '');

const debouncedSearch = useDebounceFn(() => {
    router.get(myShopDiscountsRoutes.index(props.shop.slug).url, {
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
    const { current_page, per_page, total } = props.discounts.meta;
    const start = (current_page - 1) * per_page + 1;
    const end = Math.min(current_page * per_page, total);
    return { start, end, total };
});

const hasActiveFilters = computed(() =>
    !!(search.value)
);

const formatLocalTime = (utcDate: string) => {
    if (!utcDate) return '';

    const date = new Date(utcDate);

    // Format: 13th May 2026 (01:06 PM)
    const day = date.getDate();
    const month = date.toLocaleString('en', { month: 'long', timeZone: 'Africa/Nairobi' });
    const year = date.getFullYear();

    let hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';

    // Convert to 12-hour format
    hours = hours % 12;
    hours = hours ? hours : 12;

    const timeStr = `${hours.toString().padStart(2, '0')}:${minutes} ${ampm}`;

    // Add ordinal suffix to day (st, nd, rd, th)
    const ordinal = (n: number) => {
        if (n > 3 && n < 21) return 'th';
        switch (n % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
    };

    return `${day}${ordinal(day)} ${month} ${year} (${timeStr})`;
};
</script>

<template>
    <Head title="Discounts" />

    <ShopNav :shop-id="shop.id" :shop-slug="shop.slug" current-page="discounts" />

    <div class="app-container">
        <div class="header">
            <div class="info">
                <h1 class="title">{{ props.shop.name }} - Discounts</h1>
            </div>

            <div class="search">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or slug..."
                />
            </div>

            <Link :href="myShopDiscountsRoutes.create(shop.slug).url">
                <Button>New Discount</Button>
            </Link>
        </div>

        <div class="table-wrapper">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Value</TableHead>
                        <TableHead>Applies To</TableHead>
                        <TableHead>Schedule</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="discount in discountsWithRealtimeStatus" :key="discount.id">
                        <TableCell
                            :class="{
                                'font-medium': true,
                                'text-red-600': discount.status !== 'Active'  // ✅ CHANGED: Use discount.status
                            }"
                        >
                            {{ discount.name }}
                        </TableCell>
                        <TableCell>{{ discount.type_label }}</TableCell>
                        <TableCell>{{ discount.formatted_value }}</TableCell>
                        <TableCell>
                            {{ discount.scope_label }}
                            <span v-if="discount.targets_count > 0" class="text-xs text-gray-500 ml-1">
                                ({{ discount.targets_count }})
                            </span>
                        </TableCell>
                        <TableCell class="text-sm">
                            {{ formatLocalTime(discount.starts_at) }} - {{ formatLocalTime(discount.expires_at) }}
                        </TableCell>
                        <TableCell>
                            <span :class="{
                                'inline-block px-2 py-1 rounded-full text-xs font-medium': true,
                                'bg-green-100 text-green-800': discount.status === 'Active',
                                'bg-gray-100 text-gray-800': discount.status === 'Scheduled',
                                'bg-red-100 text-red-800': discount.status === 'Expired',
                                'bg-yellow-100 text-yellow-800': discount.status === 'Inactive'
                            }">
                                {{ discount.status }}
                            </span>
                        </TableCell>
                        <TableCell class="actions">
                            <div class="actions-wrapper">
                                <Link :href="myShopDiscountsRoutes.edit({shop: shop.slug, discount: discount.id}).url" class="action edit">
                                    <Pencil class="w-4 h-4" />
                                </Link>
                                <DeleteConfirmationDialog
                                    :url="myShopDiscountsRoutes.destroy({shop: shop.slug, discount: discount.id}).url"
                                    title="Delete Discount?"
                                    description="This discount will be deleted permanently!"
                                    confirm-text="Delete Discount">
                                    <template #trigger>
                                        <button class="action delete">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </template>
                                </DeleteConfirmationDialog>
                            </div>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="discountsWithRealtimeStatus.length === 0">
                        <TableCell colspan="7" class="text-center py-8 text-gray-500">
                            No discounts found. Click "Create Discount" to get started.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :meta="discounts.meta" />

        <div class="table-results-summary">
            <p>
                Showing {{ getDisplayRange.start }} to {{ getDisplayRange.end }}
                of {{ getDisplayRange.total }} discounts
            </p>
            <p v-if="hasActiveFilters" class="filtered-results">
                Filtered results
            </p>
        </div>
    </div>
</template>
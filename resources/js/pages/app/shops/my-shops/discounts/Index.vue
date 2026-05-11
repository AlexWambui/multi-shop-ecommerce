<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import Pagination from '@/components/custom/Pagination.vue';
import myShopsRoutes from '@/routes/my-shops';
import myShopProductsRoutes from '@/routes/my-shops/products';
import shopDiscountsRoutes from '@/routes/my-shops/discounts';

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

const breadcrumbs = computed(() => [
    { title: 'Shops', href: myShopsRoutes.show(props.shop.id).url },
    { title: 'Products', href: myShopProductsRoutes.index(props.shop.slug) },
    { title: 'Discounts', href: shopDiscountsRoutes.index(props.shop.slug) },
    { title: '', description: 'Shop details' }
]);

const page = usePage();
page.props.breadcrumbs = breadcrumbs.value;

const search = ref(props.filters?.search || '');

const debouncedSearch = useDebounceFn(() => {
    router.get(shopDiscountsRoutes.index(props.shop.slug).url, {
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
</script>

<template>
    <Head title="Discounts" />

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

            <Link :href="shopDiscountsRoutes.create(shop.slug).url">
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
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="discount in props.discounts.data" :key="discount.id">
                        <TableCell 
                            :class="{
                                'font-medium': true, 
                                'text-red-600': !discount.is_active
                            }"
                        >
                            {{ discount.name }}
                        </TableCell>
                        <TableCell>{{ discount.type_label }}</TableCell>
                        <TableCell>
                            <span class="font-semibold text-green-600">
                                {{ discount.formatted_value }}
                            </span>
                        </TableCell>
                        <TableCell>
                            {{ discount.scope_label }}
                            <span v-if="discount.targets_count > 0" class="text-xs text-gray-500 ml-1">
                                ({{ discount.targets_count }})
                            </span>
                        </TableCell>
                        <TableCell class="text-sm">
                            {{ discount.starts_at }} - {{ discount.expires_at }}
                        </TableCell>
                        <TableCell class="actions">
                            <div class="actions-wrapper">
                                <Link :href="shopDiscountsRoutes.edit({shop: shop.slug, discount: discount.id}).url" class="action edit">
                                    <Pencil class="w-4 h-4" />
                                </Link>
                                <DeleteConfirmationDialog 
                                    :url="shopDiscountsRoutes.destroy({shop: shop.slug, discount: discount.id}).url" 
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
                    <TableRow v-if="props.discounts.data.length === 0">
                        <TableCell colspan="6" class="text-center py-8 text-gray-500">
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
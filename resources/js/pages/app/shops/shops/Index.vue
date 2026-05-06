<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import Input from '@/components/ui/input/Input.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Pagination from '@/components/custom/Pagination.vue';
import shopsRoutes from '@/routes/shops';
import shopCategoriesRoutes from '@/routes/shop-categories';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Shop Categories', href: shopCategoriesRoutes.index() },
            { title: 'Shops', href: shopsRoutes.index() },
        ],
    },
});

interface Shop {
    id: number;
    name: string;
    slug: string;
    contact_phone: string;
    contact_email: string;
    category_name: string;
}

interface Props {
    shops: {
        data: Shop[];
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
    router.get(shopsRoutes.index().url, {
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
    const { current_page, per_page, total } = props.shops.meta;
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
                <h1 class="title">Shops</h1>
            </div>

            <div class="search">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or slug..."
                />
            </div>

            <div>
                <!-- <Link href="#">
                    <Button>New Shop</Button>
                </Link> -->
            </div>
        </div>

        <div class="table-wrapper">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="id">#</TableHead>
                        <TableHead>Shop</TableHead>
                        <TableHead>Slug</TableHead>
                        <TableHead>Contact Phone</TableHead>
                        <TableHead>Contact Email</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="(shop, index) in shops.data" :key="shop.id">
                        <TableCell class="id">{{ (shops.meta.current_page - 1) * shops.meta.per_page + index + 1 }}</TableCell>
                        <TableCell>{{ shop.name }}</TableCell>
                        <TableCell>{{ shop.slug }}</TableCell>
                        <TableCell>{{ shop.contact_phone }}</TableCell>
                        <TableCell>{{ shop.contact_email }}</TableCell>
                        <TableCell>{{ shop.category_name }}</TableCell>
                        <TableCell class="actions">
                            <div class="actions-wrapper">
                                ---
                            </div>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="shops.data.length === 0">
                        <TableCell colspan="5" class="blank-table-row">
                            No shops found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :meta="shops.meta" />

        <div class="table-results-summary">
            <p>
                Showing {{ getDisplayRange.start }} to {{ getDisplayRange.end }}
                of {{ getDisplayRange.total }} shops
            </p>
            <p v-if="hasActiveFilters" class="filtered-results">
                Filtered results
            </p>
        </div>
    </div>
</template>
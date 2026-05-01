<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Link, router } from '@inertiajs/vue3';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import shopCategoriesRoutes from '@/routes/shop-categories';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Shops', href: "shops.all()" },
            { title: 'Shop Categories', href: shopCategoriesRoutes.index() },
        ],
    },
});

interface ShopCategory {
    id: number;
    name: string;
    slug: string;
    shops_count?: number;
};

interface Props {
    categories: ShopCategory[];
    search?: string;
};

const props = defineProps<Props>();

const search = ref(props.search || '');

const hasSearch = computed(() => 
    !!(search.value)
);

const debouncedSearch = useDebounceFn(() => {
    router.get(shopCategoriesRoutes.index().url, {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const clearFilters = () => {
    search.value = '';
};

watch([search], () => {
    debouncedSearch();
});
</script>

<template>
    <div class="app-container">
        <div class="header">
            <div class="info">
                <h1 class="title">Shop Categories</h1>
            </div>

            <div class="search">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or email..."
                />
            </div>

            <Link :href="shopCategoriesRoutes.create().url">
                <Button>New Category</Button>
            </Link>
        </div>

                <div class="table-wrapper">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="id">#</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Slug</TableHead>
                        <TableHead>Shops</TableHead>
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="(category, index) in props.categories" :key="category.id">
                        <TableCell class="id">{{ index + 1 }}</TableCell>
                        <TableCell>{{ category.name }}</TableCell>
                        <TableCell>{{ category.slug }}</TableCell>
                        <TableCell>shops_count</TableCell>
                        <TableCell class="actions">
                            <div class="actions-wrapper">
                                <Link :href="shopCategoriesRoutes.edit(category.id).url" class="action edit">
                                    Edit
                                </Link>
                                <span class="divider">|</span>
                                <DeleteConfirmationDialog :url="shopCategoriesRoutes.destroy(category.id).url" title="Delete Category?" description="This category will be deleted permanently!" confirm-text="Delete Category">
                                    <template #trigger>
                                        <button class="action delete">
                                            Delete
                                        </button>
                                    </template>
                                </DeleteConfirmationDialog>
                            </div>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="props.categories.length === 0">
                        <TableCell colspan="5" class="blank-table-row">
                            No categories found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
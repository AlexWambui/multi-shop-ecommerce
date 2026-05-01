<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import usersRoutes from '@/routes/users';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: usersRoutes.index().url,
            },
        ],
    },
});

// ==================== Types ====================
interface User {
    id: number;
    name: string;
    email: string;
    role_label: string;
    status: boolean;
    is_active: boolean;
}

interface Props {
    users: {
        data: User[];
        meta: {
            current_page: number;
            from: number;
            last_page: number;
            links: { url: string | null; label: string; active: boolean }[];
            path: string;
            per_page: number;
            to: number;
            total: number;
        };
        links: {
            first: string | null;
            last: string | null;
            prev: string | null;
            next: string | null;
        };
    };
    role_counts: Record<string, { count: number; label: string }>;
    filters: {
        search?: string;
        role?: string;
    };
}

// ==================== Props ====================
const props = defineProps<Props>();

// ==================== State ====================
const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');

// ==================== Computed ====================
const totalUsers = computed(() => 
    Object.values(props.role_counts).reduce((sum, role) => sum + role.count, 0)
);

const roleFilters = computed(() => 
    Object.entries(props.role_counts).map(([value, role]) => ({
        value,
        label: role.label,
        count: role.count,
    }))
);

const hasActiveFilters = computed(() => 
    !!(search.value || selectedRole.value)
);

const activeFilterText = computed(() => {
    if (selectedRole.value) {
        const role = roleFilters.value.find(r => r.value === selectedRole.value);
        return `${role?.label} role`;
    }
    if (search.value) return `"${search.value}"`;
    return '';
});

// ==================== Role Badge Configuration ====================
const roleBadgeConfig: Record<string, string> = {
    'Super Admin': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    'Admin': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'Seller': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'Customer': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
};

const getRoleBadgeClass = (roleLabel: string): string => 
    roleBadgeConfig[roleLabel] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';

// ==================== Pagination Helpers ====================
const getDisplayRange = computed(() => {
    const { current_page, per_page, total } = props.users.meta;
    const start = (current_page - 1) * per_page + 1;
    const end = Math.min(current_page * per_page, total);
    return { start, end, total };
});

// ==================== Actions ====================
const debouncedSearch = useDebounceFn(() => {
    router.get(usersRoutes.index().url, {
        search: search.value,
        role: selectedRole.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const filterByRole = (roleValue: string) => {
    selectedRole.value = selectedRole.value === roleValue ? '' : roleValue;
};

const clearFilters = () => {
    search.value = '';
    selectedRole.value = '';
};

const isRoleActive = (roleValue: string) => selectedRole.value === roleValue;

// ==================== Watchers ====================
watch([search, selectedRole], () => {
    debouncedSearch();
});

// ==================== Helpers ====================
const getRowNumber = (index: number) => (props.users.meta.current_page - 1) * props.users.meta.per_page + index + 1;

console.log(props.users.data);
</script>

<template>
    <Head title="Users" />

    <div class="app-container">
        <div class="header">
            <div class="info">
                <h1 class="title">Users</h1>
            </div>
            
            <div class="search">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or email..."
                />
            </div>

            <Link :href="usersRoutes.create().url">
                <Button>New User</Button>
            </Link>
        </div>

        <div class="stats-filters">
            <div class="stats-filters-wrapper">
                <button
                    :class="{
                        'bg-blue-600 text-white': !selectedRole,
                        'hover:bg-gray-200 dark:hover:bg-gray-700': selectedRole
                    }"
                    @click="filterByRole('')"
                >
                    {{ totalUsers }} Users
                </button>

                <button
                    v-for="role in roleFilters"
                    :key="role.value"
                    :class="{
                        'bg-blue-600 text-white': isRoleActive(role.value),
                        'hover:bg-gray-200 dark:hover:bg-gray-700': !isRoleActive(role.value)
                    }"
                    @click="filterByRole(role.value)"
                >
                    {{ role.count }} {{ role.label }}{{ role.count !== 1 ? 's' : '' }}
                </button>

                <button v-if="hasActiveFilters" @click="clearFilters" class="clear-filters">
                    Clear Filters
                </button>
            </div>

            <p v-if="hasActiveFilters" class="active-filters-indicator">
                <span class="font-medium">Active filter:</span> {{ activeFilterText }}
            </p>
        </div>

        <div class="table-wrapper">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="id">#</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead class="actions">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="(user, index) in props.users.data" :key="user.id">
                        <TableCell class="id">{{ getRowNumber(index) }}</TableCell>
                        <TableCell :class="{ 'text-red-500': !user.is_active }">
                            {{ user.name }}
                        </TableCell>
                        <TableCell>{{ user.email || '-' }}</TableCell>
                        <TableCell>
                            <span :class="getRoleBadgeClass(user.role_label)" class="pill">
                                {{ user.role_label }}
                            </span>
                        </TableCell>
                        <TableCell class="actions">
                            <div class="actions-wrapper">
                                <Link :href="usersRoutes.edit(user.id).url" class="action edit">
                                    Edit
                                </Link>
                                <span class="divider">|</span>
                                <DeleteConfirmationDialog :url="usersRoutes.destroy(user.id).url" title="Delete User?" description="This user will be deleted permanently!" confirm-text="Delete User">
                                    <template #trigger>
                                        <button class="action delete">
                                            Delete
                                        </button>
                                    </template>
                                </DeleteConfirmationDialog>
                            </div>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="props.users.data.length === 0">
                        <TableCell colspan="5" class="blank-table-row">
                            No users found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="props.users.meta?.links?.length > 3" class="table-pagination">
            <Link
                v-for="(link, index) in props.users.meta.links"
                :key="index"
                :href="link.url ?? ''"
                class="pagination-link"
                :class="{
                    'cursor-not-allowed bg-gray-100 text-gray-500 dark:bg-gray-800': !link.url,
                    'bg-blue-600 text-white': link.active,
                    'hover:bg-gray-50 dark:hover:bg-gray-800': link.url && !link.active,
                }"
                preserve-scroll
                v-html="link.label"
            />
        </div>

        <div class="table-results-summary">
            <p>
                Showing {{ getDisplayRange.start }} to {{ getDisplayRange.end }}
                of {{ getDisplayRange.total }} users
            </p>
            <p v-if="hasActiveFilters" class="filtered-results">
                Filtered results
            </p>
        </div>
    </div>
</template>
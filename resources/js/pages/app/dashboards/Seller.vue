<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import shopsRoutes from '@/routes/my-shops';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Shops', href: shopsRoutes.index() },
            { title: '', description: ''}
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Seller Dashboard" />

    <div class="app-container Dashboard SellerDashboard">
        <div class="Hero">
            <h1>Welcome back {{ user.name }}</h1>
        </div>
    </div>
</template>
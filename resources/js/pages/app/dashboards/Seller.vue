<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Seller Dashboard" />

    <div class="Dashboard SellerDashboard">
        <h1>{{ user.name }}</h1>
        <p>{{ user.role_label }}</p>
    </div>
</template>
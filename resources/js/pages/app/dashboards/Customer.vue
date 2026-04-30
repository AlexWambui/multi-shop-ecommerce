<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Customer Dashboard" />

    <div class="Dashboard CustomerDashboard">
        <h1>{{ user.name }}</h1>
    </div>
</template>
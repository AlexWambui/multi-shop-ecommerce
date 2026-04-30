<script setup lang="ts">
import Navbar from '@/components/custom/Navbar/Index.vue';
</script>

<template>
    <div class="GuestLayout">
        <Navbar />

        <slot />
    </div>
</template>
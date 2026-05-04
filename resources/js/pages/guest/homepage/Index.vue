<script setup lang="ts">
import { provide } from 'vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import HeroSection from './components/Hero.vue';
import FeaturedShops from './components/FeaturedShops.vue';

const props = defineProps<{
    featured_shops: any[];
}>();

provide('featured_shops', props.featured_shops);
</script>

<template>
    <GuestLayout>
        <div class="HomePage">
            <HeroSection />
            <FeaturedShops />
        </div>
    </GuestLayout>
</template>
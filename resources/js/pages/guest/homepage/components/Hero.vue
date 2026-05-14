<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    stats: {
        total_active_shops: number;
        total_active_products: number;
    }
}>();
</script>

<template>
    <section class="Hero">
        <div class="hero-wrapper">
            <div class="content">
                <div class="eyebrow">Kenya's Marketplace Network</div>
                <h1 class="title">Shop local.<br><span class="hero-dim">Support bold</span><br>businesses.</h1>
                <p class="sub-title">Discover curated shops, exclusive deals, and the people behind every product.</p>
                <div class="hero-actions">
                    <Link href="/deals-page" class="btn">Browse Offers</Link>
                    <Link href="/dashboard" class="btn btn-outline">Get Started</Link>
                </div>
            </div>

            <div class="stats">
                <div class="stat">
                    <div class="number">{{ stats.total_active_shops }}+</div>
                    <div class="label">Active shops</div>
                </div>

                <div class="stat">
                    <div class="number">{{ stats.total_active_products }}+</div>
                    <div class="label">Products listed</div>
                </div>

                <div class="stat">
                    <!-- TODO: add the correct dialy shoppers stat -->
                    <div class="number">3+</div>
                    <div class="label">Daily shoppers</div>
                </div>
            </div>

            <div class="decorative-circle"></div>
        </div>
    </section>
</template>
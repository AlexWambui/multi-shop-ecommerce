<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import EmptyDeals from '@/pages/guest/components/EmptyDeals.vue';
import type { Product } from '@/types/product';
import { usePriceFormatter } from '@/composables/usePriceFormatter';

const { formatPrice } = usePriceFormatter();

const props = defineProps<{
    hot_deals: {
        data: Product[]
    };
}>();
</script>

<template>
    <section class="HotDeals">
        <div class="section-header">
            <div class="section-title">Hot Right Now</div>
            <Link href="/deals-page" class="section-link">See all deals →</Link>
        </div>
        
        <div v-if="hot_deals && hot_deals.data.length > 0" class="hotdeals-wrapper">
            <div 
                v-for="product in hot_deals.data" 
                :key="product.id"
                class="product-card_deal"
                @click="$inertia.visit(`/product-details/${product.slug}`)"
            >
                <div class="deal-icon">
                    <img :src=product.thumbnail_url :alt=product.name />
                </div>
                <div class="deal-info">
                    <h3 class="deal-name">{{ product.name }}</h3>
                    <p class="deal-shop">{{ product.shop_name }}</p>
                </div>
                <div class="deal-right">
                    <div class="deal-discount">{{ product.discount_pct }}% OFF</div>
                    <div class="deal-was">Was {{ formatPrice(product.price) }}</div>
                </div>
            </div>
        </div>

        <div v-else class="deals-empty-state">
            <EmptyDeals type="hot" />
        </div>
    </section>
</template>
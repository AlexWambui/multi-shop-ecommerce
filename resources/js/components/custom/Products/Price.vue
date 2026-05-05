<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    price: number | null | undefined;
    discountedPrice: number | null;
    discountPct: number | null;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    price: 0
})

const formatPrice = (price: number) => `${price.toLocaleString()}`;

// Computed property to handle undefined price
const hasValidPrice = computed(() => {
    return props.price !== null && props.price !== undefined && !isNaN(props.price);
});

// Create safe price values for display
const safePrice = computed(() => {
    return hasValidPrice.value ? props.price! : 0;
});

const safeDiscountedPrice = computed(() => {
    return props.discountedPrice !== null && !isNaN(props.discountedPrice) 
        ? props.discountedPrice 
        : null;
});
</script>

<template>
    <div class="flex items-center gap-2 flex-wrap">
        <template v-if="safeDiscountedPrice !== null && discountPct !== null && hasValidPrice">
            <span :class="{
                'text-sm font-semibold': size === 'sm',
                'text-base font-semibold': size === 'md',
                'text-xl font-bold': size === 'lg',
            }" class="text-gray-900 dark:text-foreground">
                KES {{ formatPrice(safeDiscountedPrice) }}
            </span>
            <span :class="{
                'text-xs': size === 'sm',
                'text-sm': size === 'md',
                'text-base': size === 'lg',
            }" class="text-gray-400 line-through">
                KES {{ formatPrice(safePrice) }}
            </span>
            <span :class="{
                'text-xs px-1.5 py-0.5': size === 'sm',
                'text-xs px-2 py-0.5': size === 'md',
                'text-sm px-2.5 py-1': size === 'lg',
            }" class="bg-green-100 text-green-600 font-medium rounded-full">
                {{ discountPct }}% Off
            </span>
        </template>
        <template v-else-if="hasValidPrice">
            <span :class="{
                'text-sm font-semibold': size === 'sm',
                'text-base font-semibold': size === 'md',
                'text-xl font-bold': size === 'lg',
            }" class="text-gray-900 dark:text-foreground">
                KES {{ formatPrice(safePrice) }}
            </span>
        </template>
        <template v-else>
            <span class="text-gray-500">Price unavailable</span>
        </template>
    </div>
</template>
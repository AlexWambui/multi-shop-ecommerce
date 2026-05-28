<script setup lang="ts">
import ShopNav from './components/ShopNav.vue';
import { usePriceFormatter } from '@/composables/usePriceFormatter';

const { formatPrice } = usePriceFormatter();

interface Shop {
    id: number;
    name: string;
    slug: string;
};

interface Stat {
    total_products: number;
    total_orders: number;
    total_orders_today: number;
    total_orders_yesterday: number;
    trend: number;
    percentage_change: number;
    revenue: number;
    revenue_last_month: number;
    revenue_trend: number;
    revenue_percentage_change: number;
    current_month: string;
    last_month: string;
};

interface Props {
    shop: Shop;
    stats: Stat;
}

const props = defineProps<Props>();
</script>

<template>
    <ShopNav :shop-id="shop.id" :shop-slug="shop.slug" current-page="shop" />

    <div class="app-container MyShopShowPage">
        <h1>{{ shop.name }}</h1>

        <div class="Stats">
            <div class="stats-wrapper">
                <div class="stat">
                    <div class="label">Revenue ({{ stats.current_month }})</div>
                    <div class="number">{{ formatPrice(stats.revenue) }}</div>
                    <div class="extras flex items-center gap-2">
                        <span class="text-gray-600">vs {{ stats.last_month }}:</span>
                        <div class="flex items-center gap-1">
                            <span
                                v-if="stats.revenue_trend > 0"
                                class="text-green-600 text-sm"
                            >
                                &uarr; +{{ formatPrice(stats.revenue_trend) }}
                            </span>
                            <span
                                v-else-if="stats.revenue_trend < 0"
                                class="text-red-600 text-sm"
                            >
                                &darr; {{ formatPrice(Math.abs(stats.revenue_trend)) }}
                            </span>
                            <span v-else class="text-gray-400 text-sm">
                                → {{ formatPrice(0) }}
                            </span>
                            <span
                                v-if="stats.revenue_percentage_change !== 0"
                                :class="stats.revenue_trend > 0 ? 'text-green-600' : 'text-red-600'"
                                class="text-xs"
                            >
                                ({{ stats.revenue_trend > 0 ? '+' : '' }}{{ stats.revenue_percentage_change }}%)
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stat">
                    <div class="label">Orders</div>
                    <div class="number">{{ stats.total_orders }}</div>
                    <div class="extras flex items-center gap-2">
                        <span class="text-gray-600">Today:</span>
                        <span class="font-semibold">{{ stats.total_orders_today }}</span>

                        <div v-if="stats.trend !== undefined" class="flex items-center gap-1">
                            <span
                                v-if="stats.trend > 0"
                                class="text-green-600 text-sm"
                            >
                                &uarr; +{{ stats.trend }}
                            </span>
                            <span
                                v-else-if="stats.trend < 0"
                                class="text-red-600 text-sm"
                            >
                                &darr; {{ stats.trend }}
                            </span>
                            <span v-else class="text-gray-400 text-sm">
                                → 0
                            </span>
                            <span class="text-gray-400 text-xs">vs yesterday</span>
                        </div>
                    </div>
                </div>

                <div class="stat">
                    <div class="label">Products</div>
                    <div class="number">{{ stats.total_products }}</div>
                    <!-- TODO: Show actual product with low stock -->
                    <div class="extras">&darr; 3 low stock</div>
                </div>
            </div>
        </div>
    </div>
</template>

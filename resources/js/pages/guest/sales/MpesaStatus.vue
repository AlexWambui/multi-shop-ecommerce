<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    order: any;
    order_uuid: string;
    checkout_request_id: string | null;
    payment_id: number | null;
}>();

const status = ref('pending');
const message = ref('Waiting for payment confirmation...');
let pollingInterval: number | null = null;

const checkPaymentStatus = async () => {
    try {
        const response = await fetch(`/payment/mpesa/${props.order_uuid}/query-status`);
        const data = await response.json();

        if (data.success && data.status === 'completed') {
            status.value = 'completed';
            message.value = data.message;
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            setTimeout(() => {
                router.visit(`/order-details/${props.order_uuid}`);
            }, 3000);
        } else if (data.status === 'cancelled') {
            status.value = 'cancelled';
            message.value = data.message;
            if (pollingInterval) clearInterval(pollingInterval);
        } else if (data.status === 'failed') {
            status.value = 'failed';
            message.value = data.message;
            if (pollingInterval) clearInterval(pollingInterval);
        }
    } catch (error) {
        console.error('Failed to check payment status:', error);
    }
};

onMounted(() => {
    pollingInterval = window.setInterval(checkPaymentStatus, 3000);
    checkPaymentStatus();
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<template>
    <Head title="M-Pesa Payment Status" />

    <GuestLayout>
        <div class="main_container MpesaStatusPage">
            <div class="Hero">
                <h1>Payment Status</h1>
            </div>

            <div class="status-wrapper">
                <div class="status-card" :class="status">
                    <div class="status-icon">
                        <span v-if="status === 'pending'">⏳</span>
                        <span v-else-if="status === 'completed'">✅</span>
                        <span v-else-if="status === 'cancelled'">❌</span>
                        <span v-else-if="status === 'failed'">⚠️</span>
                    </div>

                    <h2>{{ message }}</h2>

                    <div v-if="status === 'pending'" class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Please check your phone and enter your M-Pesa PIN</p>
                        <p class="small-note">This page will update automatically once payment is confirmed.</p>
                    </div>

                    <div v-if="status !== 'pending'" class="action-buttons">
                        <button @click="router.visit('/checkout')" class="try-again">
                            Try Again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.MpesaStatusPage {
    min-height: 100vh;
    background: #f5f5f5;
    padding: 40px 20px;
}

.Hero {
    text-align: center;
    margin-bottom: 40px;
}

.Hero h1 {
    font-size: 32px;
    color: #333;
}

.status-wrapper {
    max-width: 500px;
    margin: 0 auto;
}

.status-card {
    background: white;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.status-icon span {
    font-size: 64px;
    display: inline-block;
    margin-bottom: 20px;
}

.status-card.pending .status-icon span {
    animation: pulse 1.5s infinite;
}

.loading-spinner {
    margin-top: 30px;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #4CAF50;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

.small-note {
    font-size: 12px;
    color: #666;
    margin-top: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.action-buttons {
    margin-top: 30px;
}

.try-again {
    padding: 12px 24px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
}

.status-card.completed .status-icon span {
    color: #4CAF50;
}

.status-card.cancelled .status-icon span,
.status-card.failed .status-icon span {
    color: #f44336;
}

.status-card.completed h2,
.status-card.completed .status-icon span {
    animation: none;
}
</style>

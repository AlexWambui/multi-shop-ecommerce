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
const attempt = ref(0);
const MAX_ATTEMPTS = 25;
let timeoutId: number | null = null;

const checkPaymentStatus = async () => {
    attempt.value++;
    
    try {
        const response = await fetch(`/payment/mpesa/${props.order_uuid}/query-status`);
        const data = await response.json();

        if (data.success && data.status === 'completed') {
            status.value = 'completed';
            message.value = data.message;
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
            setTimeout(() => {
                router.visit(`/order-details/${props.order_uuid}`);
            }, 3000);
            return;
        } 
        
        if (data.status === 'cancelled') {
            status.value = 'cancelled';
            message.value = data.message;
            if (timeoutId) clearTimeout(timeoutId);
            return;
        } 
        
        if (data.status === 'failed') {
            status.value = 'failed';
            message.value = data.message;
            if (timeoutId) clearTimeout(timeoutId);
            return;
        }
        
        // Still pending - schedule next check with exponential backoff
        if (attempt.value < MAX_ATTEMPTS) {
            // Exponential backoff: 3s, 6s, 10s, 15s, 22s, 32s, ...
            const delay = Math.min(3000 * Math.pow(1.4, attempt.value - 1), 30000);
            // Add jitter to prevent thundering herd
            const jitter = Math.random() * 2000;
            const actualDelay = Math.min(delay + jitter, 30000);
            
            console.log(`Attempt ${attempt.value}, next check in ${(actualDelay/1000).toFixed(1)}s`);
            
            timeoutId = window.setTimeout(checkPaymentStatus, actualDelay);
        } else {
            // Max attempts reached - show timeout message
            status.value = 'pending';
            message.value = 'Payment is taking longer than expected. Please check your M-Pesa messages.';
            // Add a retry button
        }
    } catch (error) {
        console.error('Failed to check payment status:', error);
        // Retry with exponential backoff on error too
        if (attempt.value < MAX_ATTEMPTS) {
            const delay = Math.min(3000 * Math.pow(1.5, attempt.value - 1), 30000);
            timeoutId = window.setTimeout(checkPaymentStatus, delay);
        }
    }
};

// Start polling with initial delay
onMounted(() => {
    // First check after 3 seconds, then exponential backoff
    timeoutId = window.setTimeout(checkPaymentStatus, 3000);
});

onUnmounted(() => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
});

// Manual retry function
const retryCheck = () => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    attempt.value = 0;
    message.value = 'Retrying...';
    timeoutId = window.setTimeout(checkPaymentStatus, 1000);
};
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
                        <p class="small-note">
                            Attempt {{ attempt }} of {{ MAX_ATTEMPTS }} • 
                            Checking every few seconds...
                        </p>
                        <button @click="retryCheck" class="retry-btn">
                            Check Now
                        </button>
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
/* ... keep your existing styles ... */

.retry-btn {
    margin-top: 15px;
    padding: 8px 20px;
    background: #2196F3;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
}

.retry-btn:hover {
    background: #1976D2;
}

.small-note {
    font-size: 13px;
    color: #666;
    margin: 10px 0;
}
</style>
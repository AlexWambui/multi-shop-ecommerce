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
            message.value = 'Payment successful! ✅';
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
            message.value = 'Payment was cancelled';
            if (timeoutId) clearTimeout(timeoutId);
            return;
        } 
        
        if (data.status === 'failed') {
            status.value = 'failed';
            message.value = 'Payment failed. Please try again.';
            if (timeoutId) clearTimeout(timeoutId);
            return;
        }
        
        // Still pending - schedule next check with exponential backoff
        if (attempt.value < MAX_ATTEMPTS) {
            // More conservative: start at 5s, grow slower
            // 5s, 8s, 12s, 18s, 25s, 30s, 30s...
            const baseDelay = 5000; // Start at 5 seconds instead of 3
            const growthFactor = 1.3; // Slower growth
            const delay = Math.min(baseDelay * Math.pow(growthFactor, attempt.value - 1), 30000);
            const jitter = Math.random() * 1000;
            const actualDelay = Math.min(delay + jitter, 30000);
            
            console.log(`📡 Attempt ${attempt.value}/${MAX_ATTEMPTS}, next check in ${(actualDelay/1000).toFixed(1)}s`);
            
            timeoutId = window.setTimeout(checkPaymentStatus, actualDelay);
        } else {
            // Max attempts reached
            status.value = 'pending';
            message.value = 'Payment is taking longer than expected. Please check your M-Pesa messages or contact support.';
        }
    } catch (error) {
        console.error('Failed to check payment status:', error);
        // Retry with exponential backoff on error too
        if (attempt.value < MAX_ATTEMPTS) {
            const delay = Math.min(5000 * Math.pow(1.5, attempt.value - 1), 30000);
            timeoutId = window.setTimeout(checkPaymentStatus, delay);
        }
    }
};

// Start polling with initial delay
onMounted(() => {
    // First check after 5 seconds (give M-Pesa time to process)
    timeoutId = window.setTimeout(checkPaymentStatus, 5000);
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
    timeoutId = window.setTimeout(checkPaymentStatus, 2000);
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
                            Attempt {{ attempt }} of {{ MAX_ATTEMPTS }}
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
    font-size: 13px;
    color: #666;
    margin: 10px 0;
}

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
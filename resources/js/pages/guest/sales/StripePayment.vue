<!-- resources/js/pages/payment/StripePayment.vue -->
<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { ref } from 'vue';

const props = defineProps<{
    order_total?: number;
}>();

const form = useForm({
    card_number: '',
    expiry_date: '',
    cvv: '',
    card_name: '',
});

const isProcessing = ref(false);

const submitPayment = () => {
    isProcessing.value = true;

    // Simulate Stripe payment processing
    setTimeout(() => {
        isProcessing.value = false;

        // Redirect to order confirmation on success
        router.visit('/orders');
    }, 2000);
};

// Format card number with spaces
const formatCardNumber = (value: string) => {
    const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    const matches = v.match(/\d{4,16}/g);
    const match = matches && matches[0] || '';
    const parts = [];

    for (let i = 0, len = match.length; i < len; i += 4) {
        parts.push(match.substring(i, i + 4));
    }

    if (parts.length) {
        return parts.join(' ');
    } else {
        return value;
    }
};

// Format expiry date (MM/YY)
const formatExpiry = (value: string) => {
    const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    if (v.length >= 2) {
        return v.substring(0, 2) + '/' + v.substring(2, 4);
    }
    return v;
};
</script>

<template>
    <Head title="Stripe Payment" />

    <GuestLayout>
        <div class="main_container PaymentPage StripePage">
            <div class="Hero">
                <h1>Stripe Payment</h1>
            </div>

            <div class="payment-page-wrapper">
                <div class="payment-card">
                    <div class="payment-header">
                        <div class="stripe-badge">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm0 13c-2.33 0-4.31-1.46-5.11-3.5h10.22c-.8 2.04-2.78 3.5-5.11 3.5z" fill="#6772E5"/>
                            </svg>
                            <span>Stripe Checkout</span>
                        </div>
                        <h2>Complete Your Payment</h2>
                    </div>

                    <div class="payment-details">
                        <div class="payment-summary">
                            <div class="summary-row">
                                <span>Order Total:</span>
                                <strong>KSH {{ order_total }}</strong>
                            </div>
                            <div class="summary-row">
                                <span>Payment Method:</span>
                                <strong>Credit / Debit Card</strong>
                            </div>
                        </div>

                        <form @submit.prevent="submitPayment" class="payment-form">
                            <div class="form-group">
                                <label for="card_name">Name on Card</label>
                                <input
                                    type="text"
                                    id="card_name"
                                    v-model="form.card_name"
                                    placeholder="John Doe"
                                    class="payment-input"
                                    required
                                />
                            </div>

                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input
                                    type="text"
                                    id="card_number"
                                    v-model="form.card_number"
                                    @input="form.card_number = formatCardNumber(form.card_number)"
                                    placeholder="4242 4242 4242 4242"
                                    maxlength="19"
                                    class="payment-input"
                                    required
                                />
                                <div class="card-icons">
                                    <span class="card-icon visa">Visa</span>
                                    <span class="card-icon mastercard">Mastercard</span>
                                    <span class="card-icon amex">Amex</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input
                                        type="text"
                                        id="expiry_date"
                                        v-model="form.expiry_date"
                                        @input="form.expiry_date = formatExpiry(form.expiry_date)"
                                        placeholder="MM/YY"
                                        maxlength="5"
                                        class="payment-input"
                                        required
                                    />
                                </div>

                                <div class="form-group half">
                                    <label for="cvv">CVV</label>
                                    <input
                                        type="text"
                                        id="cvv"
                                        v-model="form.cvv"
                                        placeholder="123"
                                        maxlength="4"
                                        class="payment-input"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="secure-badge">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" fill="#4CAF50"/>
                                </svg>
                                <span>Secure payment encrypted with SSL</span>
                            </div>

                            <button
                                type="submit"
                                class="pay-button stripe-button"
                                :disabled="isProcessing"
                            >
                                <span v-if="!isProcessing">Pay KSH {{ order_total }}</span>
                                <span v-else>Processing...</span>
                            </button>

                            <div class="test-card-info">
                                <p>Test Card Information:</p>
                                <ul>
                                    <li>Card: 4242 4242 4242 4242</li>
                                    <li>Expiry: Any future date (e.g., 12/30)</li>
                                    <li>CVV: Any 3 digits (e.g., 123)</li>
                                </ul>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.PaymentPage {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px;
}

.StripePage .Hero h1 {
    color: white;
}

.payment-page-wrapper {
    max-width: 500px;
    margin: 0 auto;
}

.payment-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.payment-header {
    text-align: center;
    margin-bottom: 30px;
}

.stripe-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f6f9fc;
    padding: 8px 16px;
    border-radius: 20px;
    margin-bottom: 20px;
}

.stripe-badge span {
    font-weight: bold;
    color: #6772E5;
}

.payment-header h2 {
    color: #333;
    font-size: 22px;
}

.payment-summary {
    background: #f7fafc;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    font-size: 14px;
}

.payment-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.payment-input:focus {
    outline: none;
    border-color: #6772E5;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-group.half {
    flex: 1;
}

.card-icons {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.card-icon {
    font-size: 12px;
    padding: 2px 8px;
    background: #f1f1f1;
    border-radius: 4px;
    color: #666;
}

.secure-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 20px 0;
    padding: 10px;
    background: #f0fdf4;
    border-radius: 6px;
    font-size: 12px;
    color: #16a34a;
}

.pay-button {
    width: 100%;
    padding: 14px;
    background: #6772E5;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
}

.pay-button:hover:not(:disabled) {
    background: #5469d4;
}

.pay-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.test-card-info {
    margin-top: 20px;
    padding: 15px;
    background: #fff3cd;
    border: 1px solid #ffecb5;
    border-radius: 6px;
}

.test-card-info p {
    font-weight: bold;
    margin-bottom: 8px;
    color: #856404;
}

.test-card-info ul {
    margin: 0;
    padding-left: 20px;
}

.test-card-info li {
    font-size: 12px;
    color: #856404;
    margin-bottom: 4px;
}
</style>

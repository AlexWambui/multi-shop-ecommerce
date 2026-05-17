<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { usePriceFormatter } from '@/composables/usePriceFormatter';

const { formatPrice } = usePriceFormatter();

const props = defineProps<{
    order_total?: number;
    contact_phone?: string | null;
    checkout_data?: any;
}>();

const form = useForm({
    phone: props.contact_phone || '',
});

const submitPayment = () => {
    form.post('/payment/mpesa/process', {
        preserveScroll: true,
        onSuccess: () => {
            // Process successful mpesa processing
            // router.visit('/orders');
        },
        onError: (errors) => {
            console.error('Payment failed:', errors);
        },
    });
};
</script>

<template>
    <Head title="MPesa Payment" />

    <GuestLayout>
        <div class="main_container MpesaPaymentPage">
            <div class="Hero">
                <h1>MPesa Payment</h1>
            </div>

            <div class="payment-page-wrapper">
                <div class="payment-info">
                    <div class="payment-card">
                        <div class="header">
                            <h2>Complete MPesa Payment</h2>
                        </div>


                        <form @submit.prevent="submitPayment" class="payment-form">
                            <div class="inputs-group">
                                <label for="phone">Phone Number for Payment</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    v-model="form.phone"
                                    placeholder="2547XXXXXXXX"
                                    class="payment-input"
                                />
                                <p class="help-text">
                                    Enter the MPesa registered phone number to receive the STK push
                                </p>
                                <InputError :message="form.errors.phone" />
                            </div>

                            <button
                                type="submit"
                                class="pay-button"
                                :disabled="form.processing"
                            >
                                <span v-if="!form.processing">Pay with MPesa</span>
                                <span v-else>Processing...</span>
                            </button>

                            <div class="cancel-payment">
                                <Link href="/checkout">Cancel and go back to checkout form</Link>
                            </div>
                        </form>

                        <div class="instructions">
                            <p>How to pay:</p>
                            <ol>
                                <li>Enter the correct MPesa number</li>
                                <li>Wait for the STK Prompt on your phone</li>
                                <li>Enter your MPesa PIN to complete payment</li>
                                <li>Wait for confirmation</li>
                            </ol>
                        </div>

                        <div class="details">
                            <div class="order-summary">
                                <h3>Order Summary</h3>
                                <div class="summary-item">
                                    <span>Name:</span>
                                    <strong>{{ checkout_data?.name }}</strong>
                                </div>

                                <div class="summary-item">
                                    <span>Email:</span>
                                    <strong>{{ checkout_data?.email }}</strong>
                                </div>

                                <p class="summary-item">
                                    <span>Contact Number:</span>
                                    <strong>{{ contact_phone }} <span class="note">(Used for communication)</span></strong>
                                </p>

                                <div class="summary-item" v-if="checkout_data?.delivery_method === 'delivery'">
                                    <span>Delivery:</span>
                                    <strong>Yes</strong>
                                </div>

                                <div class="summary-item">
                                    <span>Subtotal:</span>
                                    <strong>KSH {{ formatPrice(checkout_data?.subtotal) || 0 }}</strong>
                                </div>

                                <div class="summary-item" v-if="checkout_data?.shipping_cost > 0">
                                    <span>Shipping Cost:</span>
                                    <strong>KSH {{ formatPrice(checkout_data?.shipping_cost) || 0 }}</strong>
                                </div>

                                <div class="summary-item total-row">
                                    <span>Total Amount:</span>
                                    <strong>KSH {{ formatPrice(order_total) || 0 }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

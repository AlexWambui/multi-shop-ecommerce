<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import myShopOrdersRoutes from '@/routes/my-shops/orders';
import { usePriceFormatter } from '@/composables/usePriceFormatter';

const { formatPrice } = usePriceFormatter();

interface OrderStatus {
    id: number;
    name: string;
};

interface OrderItem {
    product_name_snapshot: string;
    quantity: number;
    unit_price: number;
    total_price?: number;
};

interface Order {
    id: number;
    order_number: string;
    total_amount: number;
    order_status: number | null;
    order_status_label: string;
    payment_status: boolean;
    payment_status_label: string;
    created_at: string;
    customer_name: string;
    customer_phone_number: string;
    delivery_method: string;
    delivery_location: string;
    delivery_area: string;
    delivery_address: string;
    order_items: OrderItem[];
    subtotal: number;
    shipping_cost: number;
    payment_method_label: string;
    payment_transaction_id: string;
    payment_amount: number;
    notes: string;
};

interface Shop {
    id: number;
    uuid: string;
    name: string;
    slug: string;
};

const props = defineProps<{
    order: {
        data: Order
    },
    shop: Shop,
    order_statuses: OrderStatus[]
}>();

const form = useForm({
    order_status: props.order.data.order_status,
    notes: props.order.data.notes
})

const submitForm = () => {
    form.put(myShopOrdersRoutes.update.url({shop: props.shop.slug, order: props.order.data.id}), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Edit Order" />

    <div class="EditOrderDetails">
        <div class="content-wrapper">
            <div class="order-details">
                <div>
                    <span>Order Number</span>
                    <span>{{ order.data.order_number }}</span>
                </div>
                <div>
                    <span>Customer</span>
                    <span>{{ order.data.customer_name }}</span>
                </div>
                <div>
                    <span>Phone Number</span>
                    <span>{{ order.data.customer_phone_number }}</span>
                </div>
                <div>
                    <span>Delivery Method</span>
                    <span>{{ order.data.delivery_method }}</span>
                </div>
                <div>
                    <span>Delivery Location</span>
                    <span>{{ order.data.delivery_location }}</span>
                </div>
                <div>
                    <span>Delivery Area</span>
                    <span>{{ order.data.delivery_area }}</span>
                </div>
                <div>
                    <span>Delivery Address</span>
                    <span>{{ order.data.delivery_address }}</span>
                </div>
                <div>
                    <span>Date</span>
                    <span>{{ order.data.created_at }}</span>
                </div>
            </div>

            <div class="order-specifics">
                <p>Order Specifics</p>

                <div class="order">
                    <div v-for="item in order.data.order_items" class="items">
                        <span>{{ item.product_name_snapshot }}</span>
                        <span>{{ item.quantity }}</span>
                        <span>{{ formatPrice(item.unit_price) }}</span>
                    </div>

                    <div class="fiscals subtotal">
                        <span>Items Total</span>
                        <span>{{ formatPrice(order.data.subtotal) }}</span>
                    </div>

                    <div class="fiscals shipping">
                        <span>Shipping Cost</span>
                        <span>{{ formatPrice(order.data.shipping_cost) ?? 0 }}</span>
                    </div>

                    <div class="fiscals total">
                        <span>Total</span>
                        <span>{{ formatPrice(order.data.total_amount) }}</span>
                    </div>
                </div>

                <div class="payment">
                    <div class="method">
                        <span>Payment Method</span>
                        <span>{{ order.data.payment_method_label }}</span>
                    </div>
                    <div class="status">
                        <span>Payment Status</span>
                        <span>{{ order.data.payment_status_label }}</span>
                    </div>
                    <div class="transaction">
                        <span>Transaction Id</span>
                        <span>{{ order.data.payment_transaction_id }}</span>
                    </div>
                    <div class="amount">
                        <span>Amount</span>
                        <span>{{ formatPrice(order.data.payment_amount) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form">
        <div class="header">
            <Link :href="myShopOrdersRoutes.index(shop.slug).url">
                &larr;
            </Link>
            <h2 class="title">Edit Order</h2>
        </div>

        <form @submit.prevent="submitForm">
            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="order_status">Order Status</Label>
                    <Select v-model="form.order_status">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select order status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="option in order_statuses"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.order_status" />
                </div>
            </div>

            <div class="inputs-group">
                <Label for="notes">Notes</Label>
                <Textarea
                    id="notes"
                    v-model="form.notes"
                    rows="4"
                    placeholder="Notes about this order..."
                />
                <InputError :message="form.errors.notes" />
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Update Order
                </Button>

                <div>
                    <Link :href="myShopOrdersRoutes.index(shop.slug).url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </div>
        </form>
    </div>
</template>

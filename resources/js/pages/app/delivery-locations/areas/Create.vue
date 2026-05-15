<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import DeliveryAreaRoutes from '@/routes/delivery-areas';
import DeliveryLocationRoutes from '@/routes/delivery-locations';

interface DeliveryLocation {
    id: number;
    uuid: string;
    name: string;
}

const props = defineProps<{
    delivery_location: DeliveryLocation;
}>();

const form = useForm({
    name: '',
    estimated_days: '',
    shipping_cost: '',
    is_active: true,
    delivery_location_id: props.delivery_location.id
})

const submitForm = () => {
    form.post(DeliveryAreaRoutes.store.url(props.delivery_location.uuid), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Create Delivery Area" />

    <div class="form">
        <div class="header">
            <Link :href="DeliveryLocationRoutes.show(delivery_location.uuid).url">
                &larr;
            </Link>
            <h2 class="title">Create New Delivery Area</h2>
        </div>

        <form @submit.prevent="submitForm">
            <input type="hidden" v-model="form.delivery_location_id">

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="name" class="required">Name</Label>
                    <Input type="text" id="name" v-model="form.name" autocomplete="name" placeholder="Odeon" autofocus />
                    <InputError :message="form.errors.name" />
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="estimated_days" class="required">Estimated Days</Label>
                    <Input type="number" id="estimated_days" v-model="form.estimated_days" autocomplete="estimated_days" placeholder="2" />
                    <InputError :message="form.errors.estimated_days" />
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="shipping_cost" class="required">Shipping Cost</Label>
                    <Input type="number" id="shipping_cost" v-model="form.shipping_cost" autocomplete="shipping_cost" placeholder="1000" />
                    <InputError :message="form.errors.shipping_cost" />
                </div>
            </div>

            <div class="inputs-group">
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0" />
                    <input
                        type="checkbox"
                        id="is_active"
                        v-model="form.is_active"
                        value="1"
                        class="w-4 h-4 rounded"
                    />
                    <Label for="is_active">Active</Label>
                </div>
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Create Delivery Area
                </Button>

                <div>
                    <Link :href="DeliveryLocationRoutes.show(delivery_location.uuid).url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </div>
        </form>
    </div>
</template>

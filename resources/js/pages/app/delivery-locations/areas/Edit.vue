<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import DeliveryAreaRoutes from '@/routes/delivery-areas';
import DeliveryLocationRoutes from '@/routes/delivery-locations';

interface DeliveryArea {
    id: number;
    name: string;
    uuid: string;
};

interface DeliveryLocation {
    id: number;
    uuid: string;
    name: string;
}

const props = defineProps<{
    delivery_area: DeliveryArea
    delivery_location: DeliveryLocation
}>();

const form = useForm({
    name: props.delivery_area.name,
})

const submitForm = () => {
    form.put(DeliveryAreaRoutes.update.url({delivery_location: props.delivery_location.uuid, delivery_area: props.delivery_area.uuid}), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Edit Delivery Area" />

    <div class="form">
        <div class="header">
            <Link :href="DeliveryLocationRoutes.show(delivery_location.uuid).url">
                &larr;
            </Link>
            <h2 class="title">Edit Delivery Area</h2>
        </div>

        <form @submit.prevent="submitForm">
            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="name" class="required">Name</Label>
                    <Input type="text" id="name" v-model="form.name" autocomplete="name" placeholder="Name" autofocus />
                    <InputError :message="form.errors.name" />
                </div>
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Update Delivery Area
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

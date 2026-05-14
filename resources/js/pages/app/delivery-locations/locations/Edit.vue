<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import DeliveryLocationsRoutes from '@/routes/delivery-locations';

interface DeliveryLocation {
    id: number;
    uuid: string;
    name: string;
};

const props = defineProps<{
    delivery_location: DeliveryLocation
}>();

const form = useForm({
    name: props.delivery_location.name,
})

const submitForm = () => {
    form.put(DeliveryLocationsRoutes.update.url(props.delivery_location.uuid), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Edit Location" />

    <div class="form">
        <div class="header">
            <Link :href="DeliveryLocationsRoutes.index().url">
                &larr;
            </Link>
            <h2 class="title">Edit Location</h2>
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
                    Update Location
                </Button>

                <div>
                    <Link :href="DeliveryLocationsRoutes.index().url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </div>
        </form>
    </div>
</template>

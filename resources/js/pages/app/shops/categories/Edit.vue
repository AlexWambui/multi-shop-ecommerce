<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import shopCategoriesRoutes from '@/routes/shop-categories';

interface Props {
    shop_category: {
        id: number;
        name: string;
        email: string;
        role: number;
        status: number;
        is_active: boolean;
    };
}

const props = defineProps<Props>();
const shopCategory = props.shop_category;

defineOptions({
    layout: {
        title: 'Edit Shop Category',
        description: 'Update shop category',
    },
});
</script>

<template>
    <Head title="Edit Category" />

    <div class="form">
        <div class="header">
            <Link :href="shopCategoriesRoutes.index().url" class="back-link">&larr;</Link>
            <h2>Edit Category</h2>
        </div>

        <Form :action="shopCategoriesRoutes.update(shopCategory.id).url" method="put" v-slot="{ errors, processing }">
            <div class="inputs-group">
                <Label for="name" class="required">Name</Label>
                <Input
                    id="name"
                    type="text"
                    autofocus
                    autocomplete="name"
                    name="name"
                    :default-value="shopCategory.name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Update User
                </Button>

                <Link :href="shopCategoriesRoutes.index().url">
                    <Button type="button" variant="outline">
                        Cancel and return to users
                    </Button>
                </Link>
            </div>
        </Form>
    </div>
</template>

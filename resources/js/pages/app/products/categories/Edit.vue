<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import productCategoriesRoutes from '@/routes/product-categories';

defineOptions({
    layout: {
        title: 'Edit Shop Category',
        description: 'Update shop category',
    },
});

interface Props {
    product_category: {
        id: number;
        name: string;
    };
}

const props = defineProps<Props>();
const productCategory = props.product_category;
</script>

<template>
    <Head title="Edit Category" />

    <div class="form">
        <div class="header">
            <Link :href="productCategoriesRoutes.index().url" class="back-link">&larr;</Link>
            <h2>Edit Category</h2>
        </div>

        <Form :action="productCategoriesRoutes.update(productCategory.id).url" method="put" v-slot="{ errors, processing }">
            <div class="inputs-group">
                <Label for="name" class="required">Name</Label>
                <Input
                    id="name"
                    type="text"
                    autofocus
                    autocomplete="name"
                    name="name"
                    :default-value="productCategory.name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Update Category
                </Button>

                <Link :href="productCategoriesRoutes.index().url">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>
        </Form>
    </div>
</template>

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
        title: 'Create Product Category',
        description: 'Add a new product category to the system',
    },
});
</script>

<template>
    <Head title="Create Product Category" />

    <div class="form">
        <div class="header">
            <Link :href="productCategoriesRoutes.index().url">
                &larr;
            </Link>
            <h2 class="title">Create New Category</h2>
        </div>

        <Form :action="productCategoriesRoutes.store.url()" method="post" v-slot="{ errors, processing }">
            <div class="inputs-group">
                <Label for="name" class="required">Category Name</Label>
                <Input
                    id="name"
                    type="text"
                    autofocus
                    autocomplete="name"
                    name="name"
                    placeholder="Product category name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Create Category
                </Button>

                <div>
                    <Link :href="productCategoriesRoutes.index().url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </div>
        </Form>
    </div>
</template>
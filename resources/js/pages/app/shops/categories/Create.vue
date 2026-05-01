<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import shopCategoriesRoutes from '@/routes/shop-categories';

defineOptions({
    layout: {
        title: 'Create Shop Category',
        description: 'Add a new shop category to the system',
    },
});
</script>

<template>
    <Head title="Create Shop Category" />

    <div class="form">
        <div class="header">
            <Link :href="shopCategoriesRoutes.index().url">
                &larr;
            </Link>
            <h2 class="title">Create New Category</h2>
        </div>

        <Form :action="shopCategoriesRoutes.store.url()" method="post" v-slot="{ errors, processing }">
            <div class="inputs-group">
                <Label for="name" class="required">Category Name</Label>
                <Input
                    id="name"
                    type="text"
                    autofocus
                    autocomplete="name"
                    name="name"
                    placeholder="Shop category name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Create Category
                </Button>

                <div>
                    <Link :href="shopCategoriesRoutes.index().url">
                        <Button type="button" variant="outline">
                            Cancel and return to categories
                        </Button>
                    </Link>
                </div>
            </div>
        </Form>
    </div>
</template>
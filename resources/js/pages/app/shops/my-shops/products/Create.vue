<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ImagePlus, X } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import myShopProductsRoutes from '@/routes/my-shops/products';

defineOptions({
    layout: {
        title: 'Create Product',
        description: 'Add a new product to your shop',
    },
});

interface Shop {
    id: number;
    name: string;
    slug: string;
};

interface ProductCategory {
    id: number;
    name: string;
};

interface Props {
    product_categories: ProductCategory[];
    shop: Shop;
};

const props = defineProps<Props>();
</script>

<template>
    <Head title="Create Product" />

    <div class="form">
        <div class="header">
            <Link :href="myShopProductsRoutes.index(props.shop.slug).url">
                &larr;
            </Link>
            <h2 class="title">Create New Product</h2>
        </div>

        <Form :action="myShopProductsRoutes.store.url(props.shop.slug)" method="post" v-slot="{ errors, processing }">
            <input type="hidden" name="shop_id" :value="props.shop.id" />

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="name" class="required">Product Name</Label>
                    <Input type="text" name="name" id="name" placeholder="Product name" autocomplete="name" autofocus />
                    <InputError :message="errors.name" />
                </div>

                <div class="inputs-group">
                    <Label for="product_category_id">Product Category</Label>
                    <Select name="product_category_id">
                        <SelectTrigger class="w-full">
                            <SelectValue :value="null" placeholder="Select product category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem 
                                    v-for="option in props.product_categories" 
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.product_category_id" />
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="sku">SKU</Label>
                    <Input type="text" name="sku" id="sku" placeholder="SKU" autocomplete="sku" />
                    <InputError :message="errors.sku" />
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="cost_price">Cost Price</Label>
                    <Input type="number" name="cost_price" id="cost_price" placeholder="Cost Price" autocomplete="cost_price" />
                    <InputError :message="errors.cost_price" />
                </div>

                <div class="inputs-group">
                    <Label for="price" class="required">Selling Price</Label>
                    <Input type="number" name="price" id="price" placeholder="Selling Price" autocomplete="price" />
                    <InputError :message="errors.price" />
                </div>
            </div>

            <div class="inputs-group">
                <Label for="description">Description</Label>
                <Textarea name="description" id="description" placeholder="Describe your shop..." rows="4"/>
                <InputError :message="errors.description" />
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" id="is_active" value="1"  checked class="w-4 h-4 rounded border-gray-300 focus:ring-2  focus:ring-gray-900" />
                        <Label for="is_active">Active</Label>
                    </div>
                </div>

                <div class="inputs-group">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_featured" value="0" />
                        <input type="checkbox" name="is_featured" id="is_featured" value="1" class="w-4 h-4 rounded border-gray-300 focus:ring-2  focus:ring-gray-900" />
                        <Label for="is_featured">Featured Product</Label>
                    </div>
                </div>
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Create Product
                </Button>

                <div>
                    <Link :href="myShopProductsRoutes.index(props.shop.slug).url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </div>
        </Form>
    </div>
</template>
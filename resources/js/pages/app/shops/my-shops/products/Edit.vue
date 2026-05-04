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
        title: 'Edit Product',
        description: 'Edit product details',
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

interface Product {
    id: number;
    name: string;
    sku: string | null;
    description: string | null;
    cost_price: number | null;
    price: number;
    product_category_id: number | null;
    is_active: boolean;
    is_featured: boolean;
};

interface Props {
    product_categories: ProductCategory[];
    shop: Shop;
    product: Product;
};

const props = defineProps<Props>();
</script>

<template>
    <Head title="Edit Product" />

    <div class="form">
        <div class="header">
            <Link :href="myShopProductsRoutes.index(props.shop.slug).url">
                &larr;
            </Link>
            <h2 class="title">Edit Product</h2>
        </div>

        <Form :action="myShopProductsRoutes.update({ shop: props.shop.slug, product: props.product.id }).url" method="put" v-slot="{ errors, processing }">
            <input type="hidden" name="shop_id" :value="props.shop.id" />

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="name" class="required">Product Name</Label>
                    <Input type="text" name="name" id="name" :default-value="props.product.name" placeholder="Product name" autocomplete="name" />
                    <InputError :message="errors.name" />
                </div>

                <div class="inputs-group">
                    <Label for="product_category_id">Product Category</Label>
                    <Select name="product_category_id" :default-value="props.product.product_category_id ?? undefined">
                        <SelectTrigger class="w-full">
                            <SelectValue :value="null" placeholder="Select product category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="option in props.product_categories" :key="option.id" :value="option.id">
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
                    <Input type="text" name="sku" id="sku" :default-value="props.product.sku ?? ''" placeholder="SKU" autocomplete="sku" />
                    <InputError :message="errors.sku" />
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="cost_price">Cost Price</Label>
                    <Input type="number" name="cost_price" id="cost_price" :default-value="props.product.cost_price ?? ''" placeholder="Cost Price" autocomplete="cost_price" step="0.01" />
                    <InputError :message="errors.cost_price" />
                </div>

                <div class="inputs-group">
                    <Label for="price" class="required">Selling Price</Label>
                    <Input type="number" name="price" id="price" :default-value="props.product.price" placeholder="Selling Price" autocomplete="price" step="0.01" />
                    <InputError :message="errors.price" />
                </div>
            </div>

            <div class="inputs-group">
                <Label for="description">Description</Label>
                <Textarea name="description" id="description" :default-value="props.product.description ?? ''" placeholder="Describe your product..." rows="4" />
                <InputError :message="errors.description" />
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" id="is_active" value="1" :checked="props.product.is_active" class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900" />
                        <Label for="is_active">Active</Label>
                    </div>
                </div>

                <div class="inputs-group">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_featured" value="0" />
                        <input type="checkbox" name="is_featured" id="is_featured" value="1" :checked="props.product.is_featured" class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900" />
                        <Label for="is_featured">Featured Product</Label>
                    </div>
                </div>
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Update Product
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
<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import myShopDiscountsRoutes from '@/routes/my-shops/discounts';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    price: number;
}

interface Shop {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    shop: Shop;
    categories: Category[];
    products: Product[];
}>();

// Scope constants matching the backend
const SCOPE_SHOP_WIDE         = 0;
const SCOPE_PRODUCT_CATEGORY  = 1;
const SCOPE_SPECIFIC_PRODUCTS = 2;

const form = useForm({
    name:             '',
    type:             0,        // 0=percentage, 1=fixed
    value:            '',
    scope:            SCOPE_SHOP_WIDE,
    // Separate fields for different scope types
    category_ids:     [] as number[],   // For scope = 1
    product_ids:      [] as number[],   // For scope = 2
    min_order_amount: '',
    min_quantity:     '',
    starts_at:        '',
    expires_at:       '',
    is_active:        true,
});

// Watch scope changes and clear the appropriate fields
const onScopeChange = () => {
    // Clear both arrays when scope changes
    form.category_ids = [];
    form.product_ids = [];
};

// Dynamic label for the value field
const valueLabel = computed(() =>
    form.type === 1 ? 'Discount Amount (KES)' : 'Discount Percentage (%)'
);

const valuePlaceholder = computed(() =>
    form.type === 1 ? 'e.g., 500' : 'e.g., 20'
);

// Whether a target picker is needed
const needsTargets = computed(() =>
    form.scope === SCOPE_PRODUCT_CATEGORY || form.scope === SCOPE_SPECIFIC_PRODUCTS
);

// Get the selected IDs based on current scope (for validation display)
const selectedCount = computed(() => {
    if (form.scope === SCOPE_PRODUCT_CATEGORY) return form.category_ids.length;
    if (form.scope === SCOPE_SPECIFIC_PRODUCTS) return form.product_ids.length;
    return 0;
});

// Transform form data before submission based on scope
const submitForm = () => {
    form.transform((data) => ({
        // Base fields that are always included
        name: data.name,
        type: data.type,
        value: data.value,
        scope: data.scope,
        min_order_amount: data.min_order_amount,
        min_quantity: data.min_quantity,
        starts_at: data.starts_at,
        expires_at: data.expires_at,
        is_active: data.is_active,

        // Conditionally add fields based on scope
        ...(data.scope === SCOPE_PRODUCT_CATEGORY && { category_ids: data.category_ids }),
        ...(data.scope === SCOPE_SPECIFIC_PRODUCTS && { product_ids: data.product_ids }),
    })).post(myShopDiscountsRoutes.store(props.shop.slug).url, {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Create Discount" />

    <div class="form">
        <div class="header">
            <Link :href="myShopDiscountsRoutes.index(shop.slug).url">
                &larr;
            </Link>
            <h2 class="title">Create New Discount</h2>
        </div>

        <form @submit.prevent="submitForm">
            <input type="hidden" name="shop_id" :value="props.shop.id" />

            <div class="inputs-group">
                <Label for="name" class="required">Discount Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="e.g., Flash Sale, Weekend Deal"
                />
                <p class="text-xs text-gray-400">Shown to customers on product and deals pages</p>
                <InputError :message="form.errors.name" />
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="type" class="required">Discount Type</Label>
                    <select
                        id="type"
                        v-model.number="form.type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                    >
                        <option :value="0">Percentage (%)</option>
                        <option :value="1">Fixed Amount (KES)</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="inputs-group">
                    <Label for="value" class="required">{{ valueLabel }}</Label>
                    <Input
                        id="value"
                        v-model="form.value"
                        type="number"
                        step="0.01"
                        :max="form.type === 0 ? 100 : undefined"
                        min="0.01"
                        required
                        :placeholder="valuePlaceholder"
                    />
                    <!-- Warn if percentage > 100 -->
                    <p v-if="form.type === 0 && Number(form.value) > 100" class="text-xs text-red-500">
                        Percentage cannot exceed 100%
                    </p>
                    <InputError :message="form.errors.value" />
                </div>
            </div>

            <div class="inputs-group">
                <Label for="scope" class="required">Applies To</Label>
                <select
                    id="scope"
                    v-model.number="form.scope"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                    @change="onScopeChange"
                >
                    <option :value="0">All products in my shop</option>
                    <option :value="1">Specific categories</option>
                    <option :value="2">Specific products</option>
                </select>
                <InputError :message="form.errors.scope" />
            </div>

            <div v-if="form.scope === SCOPE_PRODUCT_CATEGORY" class="inputs-group">
                <Label class="required">Select Categories</Label>
                <div class="border border-gray-200 rounded-lg divide-y max-h-56 overflow-y-auto">
                    <label
                        v-for="category in categories"
                        :key="category.id"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :value="category.id"
                            v-model="form.category_ids"
                            class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900"
                        />
                        <span class="text-sm">{{ category.name }}</span>
                    </label>
                    <p v-if="categories.length === 0" class="px-4 py-3 text-sm text-gray-400">
                        No categories available.
                    </p>
                </div>
                <p class="text-xs text-gray-400">Discount applies to all products in selected categories</p>
                <InputError :message="form.errors.category_ids" />
            </div>

            <div v-if="form.scope === SCOPE_SPECIFIC_PRODUCTS" class="inputs-group">
                <Label class="required">Select Products</Label>
                <div class="border border-gray-200 rounded-lg divide-y max-h-56 overflow-y-auto">
                    <label
                        v-for="product in products"
                        :key="product.id"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :value="product.id"
                            v-model="form.product_ids"
                            class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900"
                        />
                        <span class="text-sm flex-1">{{ product.name }}</span>
                        <span class="text-xs text-gray-400">KES {{ product.price.toLocaleString() }}</span>
                    </label>
                    <p v-if="products.length === 0" class="px-4 py-3 text-sm text-gray-400">
                        No products available.
                    </p>
                </div>
                <p class="text-xs text-gray-400">{{ selectedCount }} product(s) selected</p>
                <InputError :message="form.errors.product_ids" />
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="starts_at" class="required">Starts At</Label>
                    <Input
                        id="starts_at"
                        v-model="form.starts_at"
                        type="datetime-local"
                        required
                    />
                    <InputError :message="form.errors.starts_at" />
                </div>

                <div class="inputs-group">
                    <Label for="expires_at" class="required">Expires At</Label>
                    <Input
                        id="expires_at"
                        v-model="form.expires_at"
                        type="datetime-local"
                        required
                        :min="form.starts_at"
                    />
                    <InputError :message="form.errors.expires_at" />
                </div>
            </div>

            <!-- <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="min_order_amount">Min. Order Amount (KES)</Label>
                    <Input
                        id="min_order_amount"
                        v-model="form.min_order_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="No minimum"
                    />
                    <p class="text-xs text-gray-400">Leave blank for no minimum</p>
                    <InputError :message="form.errors.min_order_amount" />
                </div>

                <div class="inputs-group">
                    <Label for="min_quantity">Min. Quantity</Label>
                    <Input
                        id="min_quantity"
                        v-model="form.min_quantity"
                        type="number"
                        min="1"
                        placeholder="No minimum"
                    />
                    <p class="text-xs text-gray-400">Leave blank for no minimum</p>
                    <InputError :message="form.errors.min_quantity" />
                </div>
            </div> -->

            <div class="inputs-group">
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="is_active"
                        v-model="form.is_active"
                        class="w-4 h-4 rounded border-gray-300 focus:ring-2 focus:ring-gray-900"
                    />
                    <Label for="is_active">Active immediately</Label>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    If unchecked, the discount is saved as a draft and won't apply to any products
                </p>
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Create Discount
                </Button>

                <div>
                    <Link :href="myShopDiscountsRoutes.index(shop.slug).url">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </div>

        </form>
    </div>
</template>
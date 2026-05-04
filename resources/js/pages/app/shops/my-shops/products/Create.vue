<script setup lang="ts">
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ImagePlus, X } from 'lucide-vue-next';
import { ref } from 'vue';
import myShopProductsRoutes from '@/routes/my-shops/products';

interface Category {
    id: number;
    name: string;
}

interface Shop {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    shop: Shop;
    product_categories: Category[];
}>();

const form = useForm({
    name: '',
    description: '',
    cost_price: '',
    price: '',
    sku: '',
    is_featured: false,
    is_active: true,
    product_category_id: null as number | null,
    images: [] as File[],
    shop_id: props.shop.id,
});

const imagePreviews = ref<string[]>([]);

const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB
const MAX_TOTAL_SIZE = 10 * 1024 * 1024; // 10MB total

const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const files = Array.from(target.files || []);
    
    if (files.length === 0) return;
    
    // 1. Validate file sizes
    const oversizedFiles = files.filter(file => file.size > MAX_FILE_SIZE);
    if (oversizedFiles.length > 0) {
        alert(`Some images exceed the 2MB limit`);
        target.value = '';
        return;
    }
    
    // 2. Validate total size
    const currentTotalSize = form.images.reduce((sum, file) => sum + file.size, 0);
    const newTotalSize = files.reduce((sum, file) => sum + file.size, 0);
    if (currentTotalSize + newTotalSize > MAX_TOTAL_SIZE) {
        alert(`Total images size cannot exceed 10MB`);
        target.value = '';
        return;
    }
    
    // 3. Validate max count
    if (form.images.length + files.length > 5) {
        alert(`Maximum 5 images allowed. You already have ${form.images.length} image(s).`);
        target.value = '';
        return;
    }
    
    // 4. Add files AND create previews
    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            form.images.push(file);
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreviews.value.push(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    });
    
    target.value = '';
};

const removeImage = (index: number) => {
    form.images.splice(index, 1);
    imagePreviews.value.splice(index, 1);
};

const submitForm = () => {
    form.post(myShopProductsRoutes.store.url(props.shop.slug), {
        preserveScroll: true,
    });
};
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

        <form @submit.prevent="submitForm" enctype="multipart/form-data">
            <input type="hidden" name="shop_id" :value="props.shop.id" />

            <div class="form-section">
                <h3 class="section-title">Basic Information</h3>

                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="name" class="required">Product Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Product name"
                            autofocus
                        />
                        <InputError :error="form.errors.name" />
                    </div>

                    <div class="inputs-group">
                        <Label for="product_category_id">Product Category</Label>
                        <Select v-model="form.product_category_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select product category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem :value="null">None</SelectItem>
                                    <SelectItem 
                                        v-for="option in product_categories" 
                                        :key="option.id"
                                        :value="option.id"
                                    >
                                        {{ option.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.product_category_id" />
                    </div>
                </div>

                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="sku">SKU</Label>
                        <Input
                            id="sku"
                            v-model="form.sku"
                            type="text"
                            placeholder="SKU"
                        />
                        <InputError :message="form.errors.sku" />
                    </div>
                </div>

                <div class="inputs-group-wrapper">
                    <div class="inputs-group">
                        <Label for="cost_price">Cost Price</Label>
                        <Input
                            id="cost_price"
                            v-model="form.cost_price"
                            type="number"
                            step="0.01"
                            placeholder="Cost Price"
                        />
                        <InputError :message="form.errors.cost_price" />
                    </div>

                    <div class="inputs-group">
                        <Label for="price" class="required">Selling Price</Label>
                        <Input
                            id="price"
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            required
                            placeholder="Selling Price"
                        />
                        <InputError :message="form.errors.price" />
                    </div>
                </div>

                <div class="inputs-group">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Describe your product..."
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="inputs-group-wrapper">
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

                    <div class="inputs-group">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_featured" value="0" />
                            <input 
                                type="checkbox" 
                                id="is_featured" 
                                v-model="form.is_featured"
                                value="1"
                                class="w-4 h-4 rounded"
                            />
                            <Label for="is_featured">Featured Product</Label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Product Images</h3>
                
                <div class="inputs-group">
                    <div class="relative w-40 h-40">
                        <div class="w-40 h-40 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                            <div class="text-center">
                                <ImagePlus class="w-10 h-10 text-gray-400 mx-auto mb-2" />
                                <p class="text-sm text-gray-500">Click to upload images</p>
                            </div>
                        </div>
                        <label class="absolute inset-0 cursor-pointer">
                            <input type="file" name="images[]" accept="image/*" multiple class="hidden" @change="handleImageChange" />
                        </label>
                    </div>
                    
                    <!-- Thumbnails for uploaded images -->
                    <div v-if="imagePreviews.length > 0" class="flex gap-2 mt-2 flex-wrap">
                        <div v-for="(preview, idx) in imagePreviews" :key="idx" class="relative w-16 h-16 rounded-lg overflow-hidden border">
                            <img :src="preview" class="w-full h-full object-cover" />
                            <button 
                                type="button" 
                                @click="removeImage(idx)" 
                                class="absolute -top-1 -right-1 p-0.5 bg-red-500 text-white rounded-full hover:bg-red-600"
                            >
                                <X class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                    
                    <p class="text-xs text-gray-400 mt-2">
                        Upload up to 5 images. First image will be the primary product image. PNG, JPG up to 2MB each.
                    </p>
                    <InputError :message="form.errors.images" />
                </div>
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
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
        </form>
    </div>
</template>
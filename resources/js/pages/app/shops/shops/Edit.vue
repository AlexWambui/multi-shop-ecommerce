<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ImagePlus, X } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import shopsRoutes from '@/routes/shops';

defineOptions({
    layout: {
        title: 'Edit Shop',
        description: 'Edit shop details',
    },
});

interface ShopCategory {
    id: number;
    name: string;
}

interface Props {
    shop: {
        id: number;
        name: string;
        custom_slug: string | null;
        description: string | null;
        contact_email: string | null;
        contact_phone: string | null;
        shop_category_id: number | null;
        logo_image: string | null;
        cover_image: string | null;
        logo_url: string;
        cover_url: string;
    };
    shop_categories: ShopCategory[];
}

const props = defineProps<Props>();

const logoPreview = ref<string | null>(props.shop.logo_url);
const coverPreview = ref<string | null>(props.shop.cover_url);

const handleLogoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    // Clear the file input preview
    const fileInput = document.querySelector('input[name="logo_image"]') as HTMLInputElement;
    if (fileInput) fileInput.value = '';
    logoPreview.value = null;
};

const handleCoverChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        coverPreview.value = URL.createObjectURL(file);
    }
};

const removeCover = () => {
    const fileInput = document.querySelector('input[name="cover_image"]') as HTMLInputElement;
    if (fileInput) fileInput.value = '';
    coverPreview.value = null;
};
</script>

<template>
    <Head title="Edit Shop" />

    <div class="form">
        <div class="header">
            <Link :href="shopsRoutes.index().url">
                &larr;
            </Link>
            <h2 class="title">Edit Shop</h2>
        </div>

        <Form 
            :action="shopsRoutes.update(props.shop.id).url" 
            method="post" 
            v-slot="{ errors, processing }"
        >
            <!-- Method spoofing for PUT -->
            <input type="hidden" name="_method" value="PUT" />

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="name" class="required">Shop Name</Label>
                    <Input
                        id="name"
                        type="text"
                        autofocus
                        autocomplete="name"
                        name="name"
                        :default-value="props.shop.name"
                        placeholder="Shop name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="inputs-group">
                    <Label for="shop_category_id">Shop Category</Label>
                    <Select 
                        name="shop_category_id" 
                        :default-value="props.shop.shop_category_id ?? undefined"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select shop category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem 
                                    v-for="option in props.shop_categories" 
                                    :key="option.id" 
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.shop_category_id" />
                </div>
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label for="contact_phone">Contact Phone</Label>
                    <Input
                        id="contact_phone"
                        type="tel"
                        autocomplete="contact_phone"
                        name="contact_phone"
                        :default-value="props.shop.contact_phone ?? ''"
                        placeholder="Contact Phone"
                    />
                    <InputError :message="errors.contact_phone" />
                </div>

                <div class="inputs-group">
                    <Label for="contact_email">Contact Email</Label>
                    <Input
                        id="contact_email"
                        type="email"
                        autocomplete="contact_email"
                        name="contact_email"
                        :default-value="props.shop.contact_email ?? ''"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.contact_email" />
                </div>
            </div>

            <div class="inputs-group">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    name="description"
                    rows="4"
                    :default-value="props.shop.description ?? ''"
                    placeholder="Describe your shop..."
                />
                <InputError :message="errors.description" />
            </div>

            <div class="inputs-group">
                <Label for="custom_slug">Custom URL (Optional)</Label>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">/shops/</span>
                    <Input
                        id="custom_slug"
                        name="custom_slug"
                        :default-value="props.shop.custom_slug ?? ''"
                        placeholder="your-custom-url"
                        class="flex-1"
                    />
                </div>
                <p class="text-xs text-gray-400">
                    Leave empty to auto-generate from shop name. Use only lowercase letters, numbers, and hyphens.
                </p>
                <p class="text-xs text-gray-400">
                    Your shop will be available at: /shops/{{ props.shop.custom_slug || 'your-shop-name' }}
                </p>
                <InputError :message="errors.custom_slug" />
            </div>

            <div class="inputs-group-wrapper">
                <div class="inputs-group">
                    <Label>Logo Image</Label>
                    <div class="relative w-40 h-40">
                        <div class="w-40 h-40 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                            <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                            <div v-else class="text-center">
                                <ImagePlus class="w-10 h-10 text-gray-400 mx-auto mb-2" />
                                <p class="text-sm text-gray-500">Click to upload logo image</p>
                            </div>
                        </div>
                        <button
                            v-if="logoPreview && logoPreview !== props.shop.logo_url"
                            type="button"
                            @click="removeLogo"
                            class="absolute -top-1 -right-1 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                        >
                            <X class="w-3 h-3" />
                        </button>
                        <label class="absolute inset-0 cursor-pointer">
                            <input type="file" name="logo_image" accept="image/*" class="hidden" @change="handleLogoChange" />
                        </label>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        Current: {{ props.shop.logo_image || 'No logo uploaded' }}
                    </p>
                    <InputError :message="errors.logo_image" />
                </div>

                <div class="inputs-group">
                    <Label>Cover Image</Label>
                    <div class="relative">
                        <div class="h-40 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                            <img v-if="coverPreview" :src="coverPreview" class="w-full h-full object-cover" />
                            <div v-else class="text-center">
                                <ImagePlus class="w-10 h-10 text-gray-400 mx-auto mb-2" />
                                <p class="text-sm text-gray-500">Click to upload cover image</p>
                            </div>
                        </div>
                        <button
                            v-if="coverPreview && coverPreview !== props.shop.cover_url"
                            type="button"
                            @click="removeCover"
                            class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                        >
                            <X class="w-3 h-3" />
                        </button>
                        <label class="absolute inset-0 cursor-pointer">
                            <input type="file" name="cover_image" accept="image/*" class="hidden" @change="handleCoverChange" />
                        </label>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        Current: {{ props.shop.cover_image || 'No cover uploaded' }}
                    </p>
                    <InputError :message="errors.cover_image" />
                </div>
            </div>

            <div class="submit-buttons">
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Update Shop
                </Button>

                <Link :href="shopsRoutes.index().url">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>
        </Form>
    </div>
</template>
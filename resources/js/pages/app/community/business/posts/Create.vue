<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import { ImageIcon, X, Upload } from 'lucide-vue-next';
import businessCommunityRoutes from '@/routes/business-community';
import businessPostsRoutes from '@/routes/business-posts';
import { ref } from 'vue';

interface Shop {
    id: number;
    name: string;
};

const props = defineProps<{
    shops: Shop[];
    selectedShopId?: number | null;
}>();

// Image preview state
const imagePreview = ref<string | null>(null);
const imageFile = ref<File | null>(null);

// Initialize form with selectedShopId if provided
const form = useForm({
    content: '',
    shop_id: props.selectedShopId || null,
    is_active: true,
    image: null as File | null,
});

// Handle image selection
const handleImageSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            form.setError('image', 'Please select a valid image (JPEG, PNG, WEBP, or GIF)');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            form.setError('image', 'Image size must be less than 5MB');
            return;
        }

        form.clearErrors('image');
        imageFile.value = file;
        form.image = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

// Remove selected image
const removeImage = () => {
    imagePreview.value = null;
    imageFile.value = null;
    form.image = null;

    const fileInput = document.getElementById('image-input') as HTMLInputElement;
    if (fileInput) fileInput.value = '';
};

// Submit form with transformation
const submitForm = () => {
    form
        .transform((data) => {
            const formData = new FormData();
            formData.append('shop_id', data.shop_id?.toString() || '');
            formData.append('content', data.content);
            formData.append('is_active', data.is_active ? '1' : '0');

            if (data.image) {
                formData.append('image', data.image);
            }

            return formData;
        })
        .post(businessPostsRoutes.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('content', 'image');
                removeImage();
            },
            onError: (errors) => {
                console.error('Upload errors:', errors);
            }
        });
};
</script>

<template>
    <Head title="Create Post" />

    <div class="form max-w-2xl mx-auto p-6 BusinessPostsCreatePost">
        <div class="header flex items-center gap-4 mb-6">
            <Link :href="businessCommunityRoutes.index().url" class="text-gray-600 hover:text-gray-900">
                ← Back
            </Link>
            <h2 class="title text-2xl font-bold">Create New Post</h2>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Shop Selection -->
            <div class="inputs-group">
                <Label for="shop_id">Shop</Label>
                <Select v-model="form.shop_id">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select shop" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem
                                v-for="option in props.shops"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.shop_id" />
            </div>

            <!-- Content -->
            <div class="inputs-group">
                <Label for="content" class="required">Content</Label>
                <Textarea
                    id="content"
                    v-model="form.content"
                    autocomplete="content"
                    placeholder="What's happening in your business? Share updates, announcements, or promotions..."
                    rows="4"
                    autofocus
                />
                <InputError :message="form.errors.content" />
                <div class="text-xs text-gray-500 mt-1">
                    {{ form.content.length }}/5000 characters
                </div>
            </div>

            <!-- Image Upload -->
            <div class="inputs-group">
                <Label>Add Image (Optional)</Label>

                <!-- Image Preview -->
                <div v-if="imagePreview" class="image-preview-container relative mt-2">
                    <div class="relative inline-block">
                        <img
                            :src="imagePreview"
                            alt="Preview"
                            class="max-w-full max-h-64 rounded-lg object-cover border"
                        />
                        <button
                            type="button"
                            @click="removeImage"
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Click the X to remove image</p>
                </div>

                <!-- Upload Button -->
                <div v-else class="image-upload-area">
                    <label
                        for="image-input"
                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition"
                    >
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <Upload class="w-8 h-8 text-gray-400 mb-2" />
                            <p class="mb-2 text-sm text-gray-500">
                                <span class="font-semibold">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500">
                                JPEG, PNG, WEBP, GIF (Max 5MB)
                            </p>
                        </div>
                        <input
                            id="image-input"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                            class="hidden"
                            @change="handleImageSelect"
                        />
                    </label>
                </div>

                <InputError :message="form.errors.image" />
            </div>

            <!-- Submit Buttons -->
            <div class="submit-buttons flex gap-3 pt-4">
                <Button type="submit" :disabled="form.processing" class="flex-1">
                    <Spinner v-if="form.processing" class="mr-2" />
                    <span v-if="!form.processing">Create Post</span>
                    <span v-else>Creating Post...</span>
                </Button>

                <Link :href="businessCommunityRoutes.index().url">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>
        </form>
    </div>
</template>

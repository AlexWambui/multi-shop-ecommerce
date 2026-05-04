<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Eye } from 'lucide-vue-next';
import Button from '@/components/ui/button/Button.vue';
import DeleteConfirmationDialog from '@/components/custom/DeleteConfirmation.vue';
import { dashboard } from '@/routes';
import shopsRoutes from '@/routes/my-shops';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Shop', href: shopsRoutes.index() },
            { title: 'Overview', href: dashboard() },
        ],
    },
});

interface Shop {
    id: number;
    name: string;
    category_name: string;
    description: string;
    rating: number;
    reviews_count: number;
    logo_url: string;
    cover_url: string;
    contact_email: string | null;
    contact_phone: string | null;
    is_active: boolean;
    is_verified: boolean;
    created_at: string;
}

interface Props {
    shops: Shop[];
}

const props = defineProps<Props>();
</script>

<template>
    <div class="ShopsPage">
        <div class="app-container">
            <div v-if="props.shops.length > 0" class="shops-wrapper">
                <div v-for="shop in shops" :key="shop.id" class="shop-card-app">
                    <div class="images">
                        <div class="image cover">
                            <img :src="shop.cover_url" alt="Shop Cover Image">
                            <div class="active-badge">
                                <span :class="[shop.is_active ? 'active' : 'inactive']">{{ shop.is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </div>

                        <div class="image logo">
                            <img :src="shop.logo_url" alt="Shop logo">
                        </div>
                    </div>

                    <div class="body">
                        <Link>
                            <p class="name">{{ shop.name }}</p>
                        </Link>

                        <p class="category">{{ shop.category_name }}</p>
                        <p class="description">{{ shop.description || 'No description provided' }}</p>

                        <div class="contact-info">
                            <p v-if="shop.contact_email" class="info">{{ shop.contact_email }}</p>
                            <p v-if="shop.contact_phone" class="info">{{ shop.contact_phone }}</p>
                        </div>

                        <div class="rating">
                            <span class="star">★</span> <span class="rating">{{ shop.rating ?? '0' }}</span> · <span class="reviews">{{ shop.reviews_count ?? '0' }} reviews</span>
                        </div>

                        <div class="meta">
                            <div class="date">Created {{ new Date(shop.created_at).toLocaleDateString() }}</div>

                            <div class="actions">
                                <Link :href="shopsRoutes.show(shop.id).url">
                                    <Eye class="icon show" />
                                </Link>
                                <Link :href="shopsRoutes.edit(shop.id).url">
                                    <Pencil class="icon edit" />
                                </Link>

                                <DeleteConfirmationDialog :url="shopsRoutes.destroy(shop.id).url" title="Delete Shop?" description="This shop will be deleted permanently!" confirm-text="Delete Shop">
                                    <template #trigger>
                                        <button class="action delete">
                                            <Trash2 class="icon delete" />
                                        </button>
                                    </template>
                                </DeleteConfirmationDialog>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="blank-shop-cards">
                <div class="blank-shop-card">
                    <div class="blank-shop-icon">🏪</div>
                    <h3 class="title">No shop yet</h3>
                    <p class="description">Create your first shop to start selling</p>
                    <Button as-child>
                        <Link :href="shopsRoutes.create().url">
                            <Plus class="w-4 h-4" />
                            Create Shop
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
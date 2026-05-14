<!-- resources/js/pages/guest/productdetails/ProductDetails.vue -->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { Star, Minus, Plus, ShoppingCart, Heart, Share2, Truck, RotateCcw, ShieldCheck } from 'lucide-vue-next';
import GuestLayout from '@/layouts/GuestLayout.vue';
import ProductPrice from '@/components/custom/Products/Price.vue';
import AddToCartButton from '@/pages/guest/components/AddToCartButton.vue';
import { useCartStore } from '@/store/cart';
import { usePriceFormatter } from '@/composables/usePriceFormatter';

const { formatPrice } = usePriceFormatter();

interface ProductImage {
    id: number;
    name: string;
    full_url: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    discounted_price: number;
    discount_pct: number;
    thumbnail_url: string;
    cost_price: number | null;
    current_stock: number;
    sku: string;
    category_name: string;
    is_active: boolean;
    is_featured: boolean;
    images: ProductImage[];
    shop: {
        id: number;
        name: string;
        slug: string;
        logo_url: string;
        is_verified: boolean;
    };
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    user: {
        name: string;
    };
    created_at: string;
}

const props = withDefaults(defineProps<{
    product: Product;
    related_products: Product[];
    reviews: Review[];
    showStockIndicator?: boolean;
    showAddToCart?: boolean;
    buttonText?: string;
}>(), {
    showStockIndicator: true,
    showAddToCart: true,
    buttonText: 'Add To Cart'
});

const selectedImage = ref(props.product.images?.[0]?.full_url || null);
const activeTab = ref('details');

const tabs = [
    { id: 'details', label: 'Product Details' },
    { id: 'reviews', label: `Reviews (${props.reviews.length})` },
    { id: 'shipping', label: 'Shipping & Returns' },
];

const updateCartQuantity = async (newQuantity: number) => {
    const cartItem = cartStore.items.find(item => item.product_id === props.product.id);

    if (!cartItem) return;

    if (newQuantity <= 0) {
        await cartStore.removeItem(cartItem.id);
    } else {
        await cartStore.updateQuantity(cartItem.id, newQuantity);
    }

    await cartStore.fetchCart();
};

const averageRating = computed(() => {
    if (props.reviews.length === 0) return 0;
    const sum = props.reviews.reduce((acc, review) => acc + review.rating, 0);
    return (sum / props.reviews.length).toFixed(1);
});

const cartStore = useCartStore();

// Check if product is in cart
const isInCart = computed(() => {
    return cartStore.items.some(item => item.product_id === props.product.id);
});

// Get cart quantity
const cartQuantity = computed(() => {
    const item = cartStore.items.find(item => item.product_id === props.product.id);
    return item?.quantity || 0;
});

// Determine button display text
const buttonDisplayText = computed(() => {
    if (props.buttonText !== 'Add To Cart') {
        return props.buttonText;
    }
    return isInCart.value ? `Update Cart (${cartQuantity.value})` : 'Add To Cart';
});
</script>

<template>
    <Head :title="product.name" />

    <GuestLayout>
        <div class="main_container ProductDetailsPage">
            <section class="back-button">
                <Link :href="`/shop-details/${product.shop.slug}`">
                    <Button variant="outline">
                        &larr; Back to Shop Details
                    </Button>
                </Link>
            </section>

            <section class="ProductHero">
                <div class="product-hero-wrapper">
                    <div class="product-gallery">
                        <div class="main-image">
                            <img :src="selectedImage || product.thumbnail_url" :alt="product.name" />
                        </div>
                        <div v-if="product.images && product.images.length > 1" class="thumbnail-list">
                            <div
                                v-for="image in product.images"
                                :key="image.id"
                                class="thumbnail"
                                :class="{ active: selectedImage === image.full_url }"
                                @click="selectedImage = image.full_url"
                            >
                                <img :src="image.full_url" :alt="product.name" />
                            </div>
                        </div>
                    </div>

                    <div class="product-info">
                        <div class="shop-info">
                            <Link :href="`/shop-details/${product.shop.slug}`" class="shop-link">
                                <img :src="product.shop.logo_url" :alt="product.shop.name" class="shop-logo" />
                                <span>{{ product.shop.name }}</span>
                            </Link>
                            <span v-if="product.shop.is_verified" class="verified-badge">✓ Verified</span>
                        </div>

                        <h1 class="product-title">{{ product.name }}</h1>

                        <div class="product-meta">
                            <div class="rating">
                                <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                                <span>{{ averageRating }}</span>
                                <span class="reviews-count">({{ reviews.length }} reviews)</span>
                            </div>
                            <span class="sku">SKU: {{ product.sku || 'N/A' }}</span>
                        </div>

                        <div class="product-price">
                            <ProductPrice
                                :price="product.price"
                                :discounted_price="product.discounted_price"
                                :discount_pct="product.discount_pct"
                                size="lg"
                            />
                        </div>

                        <div class="stock-status" :class="{ 'low-stock': product.current_stock <= 5 && product.current_stock > 0, 'out-of-stock': product.current_stock === 0 }">
                            <span v-if="product.current_stock > 0">✓ In Stock ({{ product.current_stock }} units available)</span>
                            <span v-else>✗ Out of Stock</span>
                        </div>

                        <div v-if="isInCart" class="quantity-selector cart-quantity-controls">
                            <span class="label">Quantity:</span>
                            <div class="quantity-controls">
                                <button
                                    @click="updateCartQuantity(cartQuantity - 1)"
                                    :disabled="cartQuantity <= 1">
                                    <Minus class="w-4 h-4" />
                                </button>
                                <span class="quantity">{{ cartQuantity }}</span>
                                <button
                                    @click="updateCartQuantity(cartQuantity + 1)"
                                    :disabled="cartQuantity >= product.current_stock">
                                    <Plus class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <AddToCartButton
                                v-if="product.current_stock > 0"
                                :product-id="product.id"
                                :product-name="product.name"
                                :stock="product.current_stock"
                                :button-text="buttonDisplayText"
                                @success="cartStore.fetchCart()"
                                class="add-to-cart-btn"
                            />
                            <button v-else class="add-to-cart-btn disabled" disabled>
                                Out of Stock
                            </button>
                            <button class="wishlist-btn">
                                <Heart class="w-4 h-4" />
                            </button>
                            <button class="share-btn">
                                <Share2 class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="trust-badges">
                            <div class="badge">
                                <Truck class="w-4 h-4" />
                                <span>Free Shipping</span>
                            </div>
                            <div class="badge">
                                <RotateCcw class="w-4 h-4" />
                                <span>30 Days Returns</span>
                            </div>
                            <div class="badge">
                                <ShieldCheck class="w-4 h-4" />
                                <span>Secure Checkout</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="TabsContent">
                <div class="tabs">
                    <div class="tabs-wrapper">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'tab-btn',
                                activeTab === tab.id ? 'active' : ''
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <div v-if="activeTab === 'details'" class="details-tab">
                    <div class="details-wrapper">
                        <div class="description">
                            <h3>Product Description</h3>
                            <p>{{ product.description || 'No description available for this product.' }}</p>
                        </div>

                        <div class="specifications">
                            <h3>Specifications</h3>
                            <div class="specs-wrapper">
                                <div>
                                    <span>SKU</span>
                                    <span>{{ product.sku || 'N/A' }}</span>
                                </div>
                                <div>
                                    <span>Category</span>
                                    <span>{{ product.category_name }}</span>
                                </div>
                                <div>
                                    <span>Availability</span>
                                    <span>{{ product.current_stock > 0 ? `${product.current_stock} In Stock` : 'Out of Stock' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'reviews'" class="reviews-tab">
                    <div v-if="reviews.length > 0" class="reviews-wrapper">
                        <div v-for="review in reviews" :key="review.id" class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        {{ review.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="reviewer-name">{{ review.user.name }}</div>
                                        <div class="review-date">{{ new Date(review.created_at).toLocaleDateString() }}</div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <Star v-for="n in 5" :key="n" class="w-3 h-3" :class="n <= review.rating ? 'filled' : 'empty'" />
                                </div>
                            </div>
                            <p class="review-comment">{{ review.comment }}</p>
                        </div>
                    </div>
                    <div v-else class="empty-reviews">
                        <Star class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <h3>No Reviews Yet</h3>
                        <p>Be the first to review this product!</p>
                    </div>
                </div>

                <!-- Shipping Tab -->
                <div v-if="activeTab === 'shipping'" class="shipping-tab">
                    <div class="shipping-wrapper">
                        <h3>Shipping Information</h3>
                        <p>Free shipping on all orders over KES 5,000. Delivery within 3-5 business days.</p>

                        <h3>Returns Policy</h3>
                        <p>30 days return policy. Items must be unused and in original packaging.</p>

                        <h3>Warranty</h3>
                        <p>1 year manufacturer warranty on all products.</p>
                    </div>
                </div>
            </section>

            <!-- Related Products Section -->
            <section v-if="related_products.length > 0" class="RelatedProducts">
                <div class="section-header">
                    <h2 class="section-title">You May Also Like</h2>
                    <Link href="/products" class="section-link">View all →</Link>
                </div>
                <div class="related-products-wrapper">
                    <div
                        v-for="product in related_products.slice(0, 4)"
                        :key="product.id"
                        class="related-product-card"
                        @click="$inertia.visit(`/product-details/${product.slug}`)"
                    >
                        <div class="image">
                            <img :src="product.images[0]?.full_url" :alt="product.name" />
                        </div>
                        <div class="info">
                            <h3 class="name">{{ product.name }}</h3>
                            <p class="price">{{ formatPrice(product.price) }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </GuestLayout>
</template>
<script setup lang="ts">
import { computed, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Sun, Moon, ShoppingCart } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import { useCartStore } from '@/store/cart';

const { appearance, updateAppearance } = useAppearance();
const cartStore = useCartStore();
const page = usePage();

const user = computed(() => page.props.auth?.user);

onMounted(() => {
    cartStore.fetchCart();
});

// Watch for user login to refresh cart
watch(user, (newUser, oldUser) => {
    if (newUser && !oldUser) {
        // User just logged in - refresh cart
        cartStore.fetchCart();
    }
});
</script>

<template>
    <header class="guest-navbar">
        <nav>
            <div class="branding">
                <Link><span>Multi</span><span>Store</span></Link>
            </div>

            <div class="links">
                <Link>Discover</Link>
                <Link>Deals & Offers</Link>
                <Link>Community</Link>
            </div>

            <div class="extras">
                <button @click="updateAppearance(appearance === 'light' ? 'dark' : 'light')" class="toggle-theme" title="Toggle theme">
                    <Sun v-if="appearance === 'light'" class="icon sun-icon" />
                    <Moon v-else class="icon moon-icon" />
                </button>

                <Link href="/cart" class="cart" title="Cart">
                    <ShoppingCart class="icon shopping-cart-icon" />
                    <span v-if="cartStore.itemCount > 0" class="cart-count">
                        {{ cartStore.itemCount > 99 ? '99+' : cartStore.itemCount }}
                    </span>
                </Link>

                <div v-if="user" class="loggedin-user-menu"></div>

                <div v-else class="auth-pages-links">
                    <Link href="/login">Login</Link>
                </div>
            </div>
        </nav>
    </header>
</template>
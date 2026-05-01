<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div class="auth-content relative flex h-full flex-col justify-between p-10 text-white">
            <!-- Background Image with Overlay -->
            <div class="absolute inset-0 overflow-hidden">
                <img src="/assets/images/woman-shopping.jpg" alt="Woman Shopping" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-black/70"></div>
            </div>
            
            <!-- Brand -->
            <div class="relative z-10 flex items-center gap-2 text-xl font-bold leading-tight">
                <AppLogoIcon class="mr-2 size-8 fill-current text-white" />
                {{ name }}
            </div>

            <!-- Center content -->
            <div class="relative z-10 space-y-4">
                <h2 class="text-xl font-semibold leading-tight">
                    Having an account helps with
                </h2>

                <ul class="mt-4 space-y-2 text-sm text-muted-foreground">
                    <li>Fast checkout workflows</li>
                    <li>Easier follow up on orders</li>
                </ul>
            </div>

            <!-- Footer -->
            <div class="relative z-10 text-xs text-muted-foreground">
                © {{ new Date().getFullYear() }} {{ name }}
            </div>
        </div>
        
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <!-- <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p> -->
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

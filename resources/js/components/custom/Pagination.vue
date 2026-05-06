<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    meta: PaginationMeta;
}>();
</script>

<template>
    <div v-if="meta?.last_page > 1" class="pagination-container">
        <div class="pagination-wrapper">
            <Link
                v-for="link in meta.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                preserve-scroll
                class="pagination-link"
                :class="{
                    'pagination-link-active': link.active,
                    'pagination-link-disabled': !link.url
                }"
            />
        </div>
    </div>
</template>
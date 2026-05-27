<script setup lang="ts">
interface Order {
    uuid: string;
}

const props = defineProps<{
    order: Order
}>();
</script>

<template>
    <h1>Order details page.</h1>
    <pre>{{  JSON.stringify(order, null, 2)  }}</pre>
</template>

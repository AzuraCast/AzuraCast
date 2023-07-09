<template>
    <div
        v-if="loading"
        v-bind="$attrs"
        class="d-flex align-items-center justify-content-center p-4"
    >
        <div
            class="spinner-border me-3"
            role="status"
            aria-hidden="true"
        />
        <strong>Loading...</strong>
    </div>
    <template v-else>
        <slot name="default" />
    </template>
</template>

<script setup>
const props = defineProps({
    loading: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <div
        v-bind="$attrs"
        class="align-items-center justify-content-center p-4"
        :class="(loading) ? 'd-flex' : 'd-none'"
    >
        <div
            class="spinner-border me-3"
            role="status"
            aria-hidden="true"
        />
        <strong>Loading...</strong>
    </div>
    <div
        v-if="!lazy || !loading"
        v-show="!loading"
        style="display: contents"
    >
        <slot name="default" />
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        loading?: boolean,
        lazy?: boolean
    }>(),
    {
        loading: false,
        lazy: false
    }
);
</script>

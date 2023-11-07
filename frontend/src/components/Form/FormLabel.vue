<template>
    <slot name="default" />

    <span
        v-if="isRequired"
        class="text-danger"
    >
        <span aria-hidden="true">*</span>
        <span class="visually-hidden">{{ $gettext('Required') }}</span>
    </span>

    <span
        v-if="advanced"
        class="badge small text-bg-primary ms-2"
    >
        {{ $gettext('Advanced') }}
    </span>
</template>

<script setup lang="ts">
const props = defineProps({
    isRequired: {
        type: Boolean,
        default: false
    },
    advanced: {
        type: Boolean,
        default: false
    }
});
</script>

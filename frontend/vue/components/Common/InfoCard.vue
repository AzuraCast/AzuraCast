<template>
    <div
        class="card-body bg-info-subtle text-info-emphasis d-flex align-items-center"
        role="alert"
        aria-live="off"
    >
        <div class="flex-shrink-0 me-2">
            <icon icon="info" />
        </div>
        <div class="flex-fill">
            <slot />
        </div>
        <div
            v-if="slots.action"
            class="flex-shrink-0 ms-2"
        >
            <slot name="action" />
        </div>
    </div>
</template>

<script setup>
import Icon from './Icon';

const slots = defineSlots();
</script>

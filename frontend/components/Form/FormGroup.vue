<template>
    <div
        class="form-group"
        v-bind="$attrs"
    >
        <label
            v-if="slots.label"
            :for="id"
        >
            <slot name="label" />
        </label>

        <slot
            :id="id"
            name="default"
        />

        <div
            v-if="slots.description"
            class="form-text"
        >
            <slot name="description" />
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps({
    id: {
        type: String,
        required: true
    }
});

const slots = defineSlots();
</script>

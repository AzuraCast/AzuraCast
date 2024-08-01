<template>
    <fieldset class="form-group">
        <legend v-if="slots.label || label">
            <slot name="label">
                {{ label }}
            </slot>
        </legend>

        <p v-if="slots.description || description">
            <slot name="description">
                {{ description }}
            </slot>
        </p>

        <slot name="default" />
    </fieldset>
</template>

<script setup lang="ts">
const props = defineProps({
    label: {
        type: String,
        default: null
    },
    description: {
        type: String,
        default: null
    }
});

const slots = defineSlots();
</script>

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
withDefaults(
    defineProps<{
        label?: string,
        description?: string
    }>(),
    {
        label: '',
        description: ''
    }
);

const slots = defineSlots();
</script>

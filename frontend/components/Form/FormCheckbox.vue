<template>
    <input
        v-model="checkboxValue"
        class="form-check-input"
        type="checkbox"
        role="switch"
    >
</template>

<script setup lang="ts">
import {useVModel} from "@vueuse/core";

const props = withDefaults(
    defineProps<{
        modelValue?: boolean
    }>(),
    {
        modelValue: false
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', modelValue: boolean): void
}>();

const checkboxValue = useVModel(props, 'modelValue', emit);
</script>

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

const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);
const checkboxValue = useVModel(props, 'modelValue', emit);
</script>

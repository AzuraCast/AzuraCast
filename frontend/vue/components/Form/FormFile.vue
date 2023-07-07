<template>
    <input
        v-bind="$attrs"
        type="file"
        class="form-control"
        @change="uploaded"
    >
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: [Object],
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'uploaded']);

const uploaded = (event) => {
    const file = event.target.files[0];

    emit('update:modelValue', file);
    emit('uploaded', file);
};
</script>

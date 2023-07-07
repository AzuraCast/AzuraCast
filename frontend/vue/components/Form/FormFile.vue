<template>
    <input
        type="file"
        class="form-control"
        v-bind="$attrs"
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

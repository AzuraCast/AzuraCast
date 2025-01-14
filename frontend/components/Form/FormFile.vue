<template>
    <input
        v-bind="$attrs"
        type="file"
        class="form-control"
        @change="uploaded"
    >
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue?: File | null
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: File): void,
    (e: 'uploaded', value: File): void
}>();

const uploaded = (event) => {
    const file = event.target.files[0];

    emit('update:modelValue', file);
    emit('uploaded', file);
};
</script>

<template>
    <input
        v-bind="$attrs"
        type="file"
        class="form-control"
        @change="uploaded"
    >
</template>

<script setup lang="ts">
const emit = defineEmits<{
    (e: 'uploaded', value: File): void
}>();

const fileModel = defineModel<File>();

const uploaded = (event) => {
    const file = event.target.files[0];

    fileModel.value = file;
    emit('uploaded', file);
};
</script>

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

const uploaded = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files !== null && target.files.length > 0) {
        const file = target.files[0];

        fileModel.value = file;
        emit('uploaded', file);
    }
};
</script>

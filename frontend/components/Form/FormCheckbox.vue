<template>
    <input
        v-model="checkboxValue"
        class="form-check-input"
        type="checkbox"
        role="switch"
    >
</template>

<script setup lang="ts">
const checkboxValue = defineModel<boolean>({
    default: false
});
</script>

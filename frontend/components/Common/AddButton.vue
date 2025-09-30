<template>
    <button
        type="button"
        class="btn btn-primary"
        @click="emit('click')"
    >
        <icon-ic-add/>
        <span>{{ text }}</span>
    </button>
</template>
<script setup lang="ts">
import IconIcAdd from "~icons/ic/baseline-add";

defineProps<{
    text: string
}>();

const emit = defineEmits<{
    (e: 'click'): void
}>();
</script>

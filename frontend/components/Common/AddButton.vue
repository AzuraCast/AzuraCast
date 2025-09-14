<template>
    <button
        type="button"
        class="btn btn-primary"
        @click="emit('click')"
    >
        <icon :icon="IconAdd" />
        <span>{{ text }}</span>
    </button>
</template>
<script setup lang="ts">
import Icon from "~/components/Common/Icons/Icon.vue";
import {IconAdd} from "~/components/Common/Icons/icons.ts";

defineProps<{
    text: string
}>();

const emit = defineEmits<{
    (e: 'click'): void
}>();
</script>

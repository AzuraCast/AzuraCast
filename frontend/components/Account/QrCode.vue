<template>
    <canvas ref="$canvas" />
</template>

<script setup lang="ts">
import { toCanvas } from "qrcode";
import { onMounted, useTemplateRef } from "vue";

const props = defineProps<{
    uri: string;
}>();

const $canvas = useTemplateRef("$canvas");

onMounted(() => {
    toCanvas($canvas.value, props.uri, (error) => {
        if (error) console.error(error);
    });
});
</script>

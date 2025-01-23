<template>
    <slot />
</template>

<script setup lang="ts">
import {useProvidePlayerStore} from "~/functions/usePlayerStore.ts";
import {nextTick, onMounted} from "vue";

useProvidePlayerStore('global');

onMounted(() => {
    void nextTick(() => {
        document.dispatchEvent(new CustomEvent("vue-ready"));
    });
});
</script>

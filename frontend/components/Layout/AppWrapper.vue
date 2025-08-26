<template>
    <slot/>

    <audio-player/>
</template>

<script setup lang="ts">
import {nextTick, onMounted} from "vue";
import AudioPlayer from "~/components/Common/AudioPlayer.vue";

onMounted(() => {
    void nextTick(() => {
        document.dispatchEvent(new CustomEvent("vue-ready"));
    });
});
</script>

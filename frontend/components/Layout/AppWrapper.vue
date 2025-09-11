<template>
    <slot/>

    <audio-player/>

    <toast-container/>
    <dialog-container/>
</template>

<script setup lang="ts">
import {nextTick, onMounted} from "vue";
import AudioPlayer from "~/components/Common/AudioPlayer.vue";
import ToastContainer from "~/components/Common/ToastContainer.vue";
import DialogContainer from "~/components/Common/DialogContainer.vue";

onMounted(async () => {
    await nextTick();

    document.dispatchEvent(new CustomEvent("vue-ready"));
});
</script>

<template>
    <slot/>

    <audio-player/>
    <toast-container/>
    <dialog-container/>
</template>

<script setup lang="ts">
import {nextTick, onMounted} from "vue";
import AudioPlayer from "~/components/Common/Audio/AudioPlayer.vue";
import ToastContainer from "~/components/Common/Toasts/ToastContainer.vue";
import DialogContainer from "~/components/Common/Dialogs/DialogContainer.vue";

onMounted(async () => {
    await nextTick();

    document.dispatchEvent(new CustomEvent("vue-ready"));
});
</script>

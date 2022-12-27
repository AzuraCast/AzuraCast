<template>
    <a v-if="src" :href="src" class="album-art" target="_blank" data-fancybox="gallery">
        <img class="album_art" :src="src" loading="lazy" alt="">
    </a>
</template>

<style scoped>
img.album_art {
    width: v-bind(widthPx);
    height: auto;
    border-radius: 5px;
}
</style>

<script setup>
import {computed} from "vue";

const props = defineProps({
    src: String,
    width: {
        type: Number,
        default: 40
    }
});

const widthPx = computed(() => {
    return props.width + 'px';
});
</script>

<template>
    <a
        v-if="src"
        v-lightbox
        :href="src"
        class="album-art"
        target="_blank"
        data-fancybox="gallery"
        :aria-label="$gettext('Enlarge Album Art')"
        :title="$gettext('Enlarge Album Art')"
    >
        <img
            class="album_art"
            :src="src"
            loading="lazy"
            alt=""
        >
    </a>
</template>

<script setup lang="ts">
import {computed} from "vue";
import {useLightbox} from "~/vendor/lightbox";

const props = withDefaults(
    defineProps<{
        src: string,
        width?: number
    }>(),
    {
        width: 40
    }
);

const widthPx = computed(() => {
    return props.width + 'px';
});

// Use lightbox if available; if not, just ignore.
const {vLightbox} = useLightbox();
</script>

<style scoped>
img.album_art {
    width: v-bind(widthPx);
    height: auto;
    border-radius: 5px;
}
</style>

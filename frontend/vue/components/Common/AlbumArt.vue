<template>
    <a
        v-if="src"
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

<script setup>
import {computed} from "vue";

const props = defineProps({
    src: {
        type: String,
        required: true
    },
    width: {
        type: Number,
        default: 40
    }
});

const widthPx = computed(() => {
    return props.width + 'px';
});
</script>

<style scoped>
img.album_art {
    width: v-bind(widthPx);
    height: auto;
    border-radius: 5px;
}
</style>

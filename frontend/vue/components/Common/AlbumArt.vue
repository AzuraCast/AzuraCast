<template>
    <a :href="src" class="album-art" target="_blank" data-fancybox="gallery">
        <b-img class="album_art" :src="src" loading="lazy"
               :style="{ width: this.width+'px', height: 'auto', 'border-radius': '5px' }"></b-img>
    </a>
</template>

<script>
export default {
    props: {
        src: String,
        width: {
            type: Number,
            default: 40
        }
    }
};
</script>

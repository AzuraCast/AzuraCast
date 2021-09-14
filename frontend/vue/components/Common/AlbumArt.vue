<template>
    <a :href="src" class="album-art" target="_blank" data-fancybox="gallery">
        <img class="album_art" :src="src" :style="{ width: this.width+'px' }">
    </a>
</template>

<style lang="scss">
.album-art img {
    height: auto;
    border-radius: 5px;
}
</style>

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

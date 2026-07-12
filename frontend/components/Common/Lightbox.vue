<template>
    <vue-easy-lightbox
        :visible="visible"
        :imgs="imgs"
        @hide="hide"
    />
</template>

<script setup lang="ts">
import { ref } from "vue";
import VueEasyLightbox from "vue-easy-lightbox";

interface Img {
    src?: string;
    title?: string;
    alt?: string;
}

type PropsImgs = Img | string | (Img | string)[];

const visible = ref(false);
const imgs = ref<PropsImgs>([]);

const show = (imageOrImages: PropsImgs) => {
    imgs.value = imageOrImages;
    visible.value = true;
};

const hide = () => {
    visible.value = false;
};

defineExpose({
    show,
    hide,
});
</script>

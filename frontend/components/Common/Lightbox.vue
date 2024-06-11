<template>
    <vue-easy-lightbox
        :visible="visible"
        :imgs="imgs"
        @hide="hide"
    />
</template>

<script setup lang="ts">
import VueEasyLightbox from "vue-easy-lightbox";
import {ref} from "vue";

const visible = ref(false);
const imgs = ref([]);

const show = (imageOrImages) => {
    imgs.value = imageOrImages;
    visible.value = true;
}

const hide = () => {
    visible.value = false;
}

defineExpose({
    show,
    hide
});
</script>

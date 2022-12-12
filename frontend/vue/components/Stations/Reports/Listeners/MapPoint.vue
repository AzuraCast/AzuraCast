<template>
    <div ref="popup-content">
        <slot/>
    </div>
</template>

<script setup>
import {get, set, templateRef} from '@vueuse/core';
import {inject, onUnmounted, ref, toRaw, watch} from 'vue';

const props = defineProps({
    position: Array
});

const $map = inject('map');
const $marker = ref();

const map = toRaw(get($map));
const marker = L.marker(props.position);
marker.addTo(map);
set($marker, marker);

const popup = new L.Popup();
const $popupContent = templateRef('popup-content');
watch(
    $popupContent,
    (content) => {
        popup.setContent(content);
        marker.bindPopup(popup);
    },
    {immediate: true}
);

onUnmounted(() => {
    get($marker).remove();
});
</script>

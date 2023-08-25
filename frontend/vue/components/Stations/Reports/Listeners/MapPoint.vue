<template>
    <div ref="$content">
        <slot />
    </div>
</template>

<script setup lang="ts">
import {inject, onUnmounted, ref, watch} from 'vue';
import {marker} from 'leaflet';

const props = defineProps({
    position: {
        type: Array,
        required: true
    }
});

const $map = inject('map');
const map = $map.value;

const mapMarker = marker(props.position);
mapMarker.addTo(map);

const popup = new L.Popup();
const $content = ref<HTMLDivElement | null>(null);

watch(
    $content,
    (newContent) => {
        popup.setContent(newContent);
        mapMarker.bindPopup(popup);
    },
    {immediate: true}
);

onUnmounted(() => {
    mapMarker.remove();
});
</script>

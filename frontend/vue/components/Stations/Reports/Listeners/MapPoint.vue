<template>
    <div ref="$content">
        <slot />
    </div>
</template>

<script setup>
import {inject, onUnmounted, ref, watch} from 'vue';
import L from 'leaflet';

const props = defineProps({
    position: {
        type: Array,
        required: true
    }
});

const $map = inject('map');
const map = $map.value;

const marker = L.marker(props.position);
marker.addTo(map);

const popup = new L.Popup();
const $content = ref(); // Template Ref

watch(
    $content,
    (newContent) => {
        popup.setContent(newContent);
        marker.bindPopup(popup);
    },
    {immediate: true}
);

onUnmounted(() => {
    marker.remove();
});
</script>

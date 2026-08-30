<template>
    <div ref="$content">
        <slot />
    </div>
</template>

<script setup lang="ts">
import { LngLatLike, Map as MapLibreMap, Marker, Popup } from "maplibre-gl";
import { onUnmounted, toRef, useTemplateRef, watch } from "vue";

const props = defineProps<{
    map: MapLibreMap;
    position: LngLatLike;
}>();

const mapMarker = new Marker().setLngLat(props.position);

watch(
    toRef(props, "map"),
    (mapRef) => {
        if (mapRef !== null) {
            mapMarker.addTo(mapRef);
        }
    },
    { immediate: true },
);

const popup = new Popup();
const $content = useTemplateRef("$content");

watch(
    $content,
    (newContent) => {
        if (newContent !== null) {
            popup.setDOMContent(newContent);
            mapMarker.setPopup(popup);
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    mapMarker.remove();
});
</script>

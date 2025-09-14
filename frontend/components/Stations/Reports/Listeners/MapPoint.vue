<template>
    <div ref="$content">
        <slot />
    </div>
</template>

<script setup lang="ts">
import {onUnmounted, toRef, useTemplateRef, watch} from "vue";
import {LatLngTuple, Map, marker, Popup} from "leaflet";

const props = defineProps<{
    map: Map,
    position: LatLngTuple
}>();

const mapMarker = marker(props.position);

watch(
    toRef(props, 'map'),
    (mapRef) => {
        if (mapRef !== null) {
            mapMarker.addTo(mapRef);
        }
    },
    {immediate: true}
);

const popup = new Popup();
const $content = useTemplateRef('$content');

watch(
    $content,
    (newContent) => {
        if (newContent !== null) {
            popup.setContent(newContent);
            mapMarker.bindPopup(popup);
        }
    },
    {immediate: true}
);

onUnmounted(() => {
    mapMarker.remove();
});
</script>

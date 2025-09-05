<template>
    <div ref="$content">
        <slot />
    </div>
</template>

<script setup lang="ts">
import {onUnmounted, useTemplateRef, watch} from "vue";
import {LatLngTuple, marker, Popup} from "leaflet";
import {useMap} from "~/components/Stations/Reports/Listeners/useMap.ts";

const props = defineProps<{
    position: LatLngTuple
}>();

const {injectMap} = useMap();
const $map = injectMap();

const mapMarker = marker(props.position);

watch(
    $map,
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

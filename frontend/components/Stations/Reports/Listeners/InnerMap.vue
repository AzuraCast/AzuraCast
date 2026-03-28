<template>
    <div
        id="leaflet-container"
        ref="$container"
    >
        <slot v-if="$map" :map="$map"/>
    </div>
</template>

<script setup lang="ts">
import {onMounted, shallowRef, useTemplateRef, watch} from "vue";
import {Control, Icon, Map, map, tileLayer} from "leaflet";
import {useTheme} from "~/functions/theme.ts";
import "leaflet-fullscreen";
import {useTranslate} from "~/vendor/gettext.ts";
import {storeToRefs} from "pinia";
import markerIconUrl from "leaflet/dist/images/marker-icon.png";
import markerIconRetinaUrl from "leaflet/dist/images/marker-icon-2x.png";
import markerShadowUrl from "leaflet/dist/images/marker-shadow.png";

defineSlots<{
    default: (props: {
        map: Map
    }) => any,
}>();

const $container = useTemplateRef('$container');

const $map = shallowRef<Map | null>(null);

const {currentTheme} = storeToRefs(useTheme());
const {$gettext} = useTranslate();

onMounted(() => {
    Icon.Default.prototype.options.iconUrl = markerIconUrl;
    Icon.Default.prototype.options.iconRetinaUrl = markerIconRetinaUrl;
    Icon.Default.prototype.options.shadowUrl = markerShadowUrl;
    Icon.Default.imagePath = "";

    // Init map
    const mapObj = map(
        $container.value!
    );
    mapObj.setView([40, 0], 1);

    mapObj.addControl(new Control.Fullscreen({
        title: {
            'false': $gettext('View Fullscreen'),
            'true': $gettext('Exit Fullscreen')
        }
    }));

    $map.value = mapObj;

    // Add tile layer
    const addTileLayer = () => {
        if (!currentTheme.value) {
            return;
        }

        const tileUrl = `https://cartodb-basemaps-{s}.global.ssl.fastly.net/${currentTheme.value}_all/{z}/{x}/{y}.png`;
        const tileAttribution = 'Map tiles by Carto, under CC BY 3.0. Data by OpenStreetMap, under ODbL.';

        tileLayer(tileUrl, {
            id: 'main',
            attribution: tileAttribution,
        }).addTo(mapObj);
    };

    addTileLayer();

    watch(currentTheme, () => {
        addTileLayer();
    });
});
</script>

<style lang="scss">
@import "leaflet/dist/leaflet.css";
@import "leaflet-fullscreen/dist/leaflet.fullscreen.css";

.leaflet-container {
    height: 300px;
    z-index: 0;
}
</style>

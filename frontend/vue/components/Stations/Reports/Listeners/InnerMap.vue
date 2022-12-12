<template>
    <div id="leaflet-container" ref="map">
        <slot v-if="$map" :map="$map"/>
    </div>
</template>

<style lang="scss">
@import 'leaflet/dist/leaflet.css';

.leaflet-container {
    height: 300px;
    z-index: 0;
}
</style>

<script setup>
import {onMounted, provide, ref} from "vue";
import L from "leaflet";
import {get, set, templateRef} from "@vueuse/core";

const props = defineProps({
    attribution: String
});

const $container = templateRef('map');
const $map = ref();

provide('map', $map);

onMounted(() => {
    // Fix issue with Leaflet icons being built in Webpack
    // https://github.com/Leaflet/Leaflet/issues/4968
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
        iconUrl: require('leaflet/dist/images/marker-icon.png'),
        shadowUrl: require('leaflet/dist/images/marker-shadow.png')
    });

    // Init map
    const map = L.map(get($container));
    map.setView([40, 0], 1);
    set($map, map);

    // Add tile layer
    const tileUrl = 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/{theme}_all/{z}/{x}/{y}.png';
    const tileAttribution = 'Map tiles by Carto, under CC BY 3.0. Data by OpenStreetMap, under ODbL.';

    L.tileLayer(tileUrl, {
        theme: App.theme,
        attribution: tileAttribution,
    }).addTo(map);

    /*
    // Add fullscreen control
    const fullscreenControl = new L.Control.Fullscreen();
    map.addControl(fullscreenControl)
     */

});
</script>

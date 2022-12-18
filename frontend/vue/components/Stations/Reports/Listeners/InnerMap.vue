<template>
    <div id="leaflet-container" ref="container">
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
import {onMounted, provide, ref, shallowRef} from "vue";
import L from "leaflet";

const props = defineProps({
    attribution: String
});

const container = ref(); // Template Ref
const $map = shallowRef();

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
    const map = L.map(container.value);
    map.setView([40, 0], 1);

    $map.value = map;

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

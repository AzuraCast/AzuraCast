<template>
    <div
        id="leaflet-container"
        ref="$container"
    >
        <slot
            v-if="$map"
            :map="$map"
        />
    </div>
</template>

<script setup>
import {onMounted, provide, ref, shallowRef, watch} from "vue";
import L from 'leaflet';
import useGetTheme from "~/functions/useGetTheme";

const $container = ref(); // Template Ref
const $map = shallowRef();

provide('map', $map);

const {theme} = useGetTheme();

onMounted(() => {
    L.Icon.Default.imagePath = '/static/img/leaflet/';

    // Init map
    const map = L.map($container.value);
    map.setView([40, 0], 1);

    $map.value = map;

    // Add tile layer
    const tileUrl = 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/{theme}_all/{z}/{x}/{y}.png';
    const tileAttribution = 'Map tiles by Carto, under CC BY 3.0. Data by OpenStreetMap, under ODbL.';

    L.tileLayer(tileUrl, {
        theme: theme.value,
        attribution: tileAttribution,
    }).addTo(map);

    watch(theme, (newTheme) => {
        L.tileLayer(tileUrl, {
            theme: newTheme,
            attribution: tileAttribution,
        }).addTo(map);
    });
});
</script>

<style lang="scss">
@import 'leaflet/dist/leaflet.css';

.leaflet-container {
    height: 300px;
    z-index: 0;
}
</style>

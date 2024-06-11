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

<script setup lang="ts">
import {onMounted, provide, ref, shallowRef, watch} from "vue";
import {Control, Icon, map, tileLayer} from 'leaflet';
import useTheme from "~/functions/theme";
import 'leaflet-fullscreen';
import {useTranslate} from "~/vendor/gettext";

const $container = ref<HTMLDivElement | null>(null);
const $map = shallowRef(null);

provide('map', $map);

const {currentTheme} = useTheme();
const {$gettext} = useTranslate();

onMounted(() => {
    Icon.Default.imagePath = '/static/img/leaflet/';

    // Init map
    const mapObj = map(
        $container.value
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
@import 'leaflet/dist/leaflet.css';
@import 'leaflet-fullscreen/dist/leaflet.fullscreen.css';

.leaflet-container {
    height: 300px;
    z-index: 0;
}
</style>

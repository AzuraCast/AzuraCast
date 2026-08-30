<template>
    <div
        id="map-container"
        ref="$container"
    >
        <slot v-if="$map" :map="$map"/>
    </div>
</template>

<script setup lang="ts">
import { layers, namedFlavor } from "@protomaps/basemaps";
import {
    addProtocol,
    FullscreenControl,
    Map as MapLibreMap,
    NavigationControl,
    StyleSpecification,
    setWorkerUrl,
} from "maplibre-gl";
import maplibreWorkerUrl from "maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url";
import { storeToRefs } from "pinia";
import { Protocol } from "pmtiles";
import { onMounted, onUnmounted, shallowRef, useTemplateRef, watch } from "vue";
import { Theme, useTheme } from "~/functions/theme.ts";
import { useAzuraCast } from "~/vendor/azuracast.ts";
import { useTranslate } from "~/vendor/gettext.ts";

defineSlots<{
    default: (props: { map: MapLibreMap }) => any;
}>();

setWorkerUrl(maplibreWorkerUrl);
addProtocol("pmtiles", new Protocol().tile);

const $container = useTemplateRef("$container");

const $map = shallowRef<MapLibreMap | null>(null);

const { currentTheme } = storeToRefs(useTheme());
const { localeShort } = useAzuraCast();
const { $gettext } = useTranslate();

const buildStyle = (theme: Theme): StyleSpecification => ({
    version: 8,
    glyphs: `${location.origin}/static/maps/assets/fonts/{fontstack}/{range}.pbf`,
    sprite: `${location.origin}/static/maps/assets/sprites/v4/${theme}`,
    sources: {
        protomaps: {
            type: "vector",
            url: `pmtiles://${location.origin}/static/maps/basemap.pmtiles`,
            attribution:
                '<a href="https://protomaps.com">Protomaps</a> © <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
        },
    },
    layers: layers("protomaps", namedFlavor(theme), { lang: localeShort }),
});

onMounted(() => {
    watch(
        currentTheme,
        (theme) => {
            if (theme === null) {
                return;
            }

            if ($map.value === null) {
                const mapObj = new MapLibreMap({
                    container: $container.value!,
                    style: buildStyle(theme),
                    center: [0, 40],
                    zoom: 0,
                    maxZoom: 9,
                    locale: {
                        "FullscreenControl.Enter": $gettext("View Fullscreen"),
                        "FullscreenControl.Exit": $gettext("Exit Fullscreen"),
                    },
                });

                mapObj.addControl(
                    new NavigationControl({ showCompass: false }),
                );
                mapObj.addControl(new FullscreenControl());

                $map.value = mapObj;
            } else {
                $map.value.setStyle(buildStyle(theme));
            }
        },
        { immediate: true },
    );
});

onUnmounted(() => {
    $map.value?.remove();
});
</script>

<style lang="scss">
@import "maplibre-gl/dist/maplibre-gl.css";

#map-container {
    height: 300px;
    z-index: 0;
}

.maplibregl-popup-content {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
}

.maplibregl-popup-anchor-top .maplibregl-popup-tip,
.maplibregl-popup-anchor-top-left .maplibregl-popup-tip,
.maplibregl-popup-anchor-top-right .maplibregl-popup-tip {
    border-bottom-color: var(--bs-body-bg);
}

.maplibregl-popup-anchor-bottom .maplibregl-popup-tip,
.maplibregl-popup-anchor-bottom-left .maplibregl-popup-tip,
.maplibregl-popup-anchor-bottom-right .maplibregl-popup-tip {
    border-top-color: var(--bs-body-bg);
}

.maplibregl-popup-anchor-left .maplibregl-popup-tip {
    border-right-color: var(--bs-body-bg);
}

.maplibregl-popup-anchor-right .maplibregl-popup-tip {
    border-left-color: var(--bs-body-bg);
}
</style>

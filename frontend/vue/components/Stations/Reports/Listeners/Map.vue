<template>
    <l-map v-if="mapPoints.length < 3000" style="height: 300px; z-index: 0;" :zoom="1" :center="[40, 0]">
        <l-control-fullscreen/>
        <l-tile-layer :url="tileUrl" :attribution="tileAttribution"></l-tile-layer>
        <l-marker v-for="l in mapPoints" :key="l.hash"
                  :lat-lng="{lat: l.location.lat, lng: l.location.lon}">
            <l-tooltip>
                IP: {{ l.ip }}<br>
                Country: {{ l.location.country }}<br>
                Region: {{ l.location.region }}<br>
                City: {{ l.location.city }}<br>
                Time connected: {{ l.connected_time }}<br>
                User Agent: {{ l.user_agent }}
            </l-tooltip>
        </l-marker>
    </l-map>
</template>

<style lang="css">
@import '../../../../../node_modules/leaflet/dist/leaflet.css';
</style>

<script>
import L from 'leaflet';
import {LMap, LMarker, LTileLayer, LTooltip} from 'vue2-leaflet';
import LControlFullscreen from 'vue2-leaflet-fullscreen';
import _ from 'lodash';

export default {
    name: 'StationReportsListenersMap',
    props: {
        attribution: String,
        listeners: Array,
    },
    components: {
        LMap,
        LTileLayer,
        LMarker,
        LTooltip,
        LControlFullscreen
    },
    data() {
        return {
            tileUrl: 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/' + App.theme + '_all/{z}/{x}/{y}.png',
            tileAttribution: 'Map tiles by Carto, under CC BY 3.0. Data by OpenStreetMap, under ODbL.',
        }
    },
    mounted() {
        // Fix issue with Leaflet icons being built in Webpack
        // https://github.com/Leaflet/Leaflet/issues/4968
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
            iconUrl: require('leaflet/dist/images/marker-icon.png'),
            shadowUrl: require('leaflet/dist/images/marker-shadow.png')
        });
    },
    computed: {
        mapPoints() {
            return _.filter(this.listeners, function (l) {
                return null !== l.location.lat && null !== l.location.lon;
            });
        }
    }
};
</script>

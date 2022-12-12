<template>
    <inner-map v-if="visibleListeners.length < 3000"
               :attribution="attribution">
        <map-point v-for="l in visibleListeners" :key="l.hash"
                   :position="[l.location.lat, l.location.lon]">
            <translate key="l-ip">IP</translate>
            : {{ l.ip }}<br>
            <translate key="l-country">Country</translate>
            : {{ l.location.country }}<br>
            <translate key="l-region">Region</translate>
            : {{ l.location.region }}<br>
            <translate key="l-city">City</translate>
            : {{ l.location.city }}<br>
            <translate key="l-time">Time</translate>
            : {{ l.connected_time }}<br>
            <translate key="l-ua">User Agent</translate>
            : {{ l.user_agent }}
        </map-point>
    </inner-map>
</template>

<script setup>
import InnerMap from "./InnerMap.vue";
import MapPoint from "./MapPoint.vue";
import {computed} from "vue";
import _ from "lodash";

const props = defineProps({
    attribution: String,
    listeners: Array,
});

const visibleListeners = computed(() => {
    return _.filter(props.listeners, function (l) {
        return null !== l.location.lat && null !== l.location.lon;
    });
});
</script>

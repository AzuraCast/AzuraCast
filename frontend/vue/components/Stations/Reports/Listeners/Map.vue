<template>
    <inner-map
        v-if="visibleListeners.length < 3000"
    >
        <map-point
            v-for="l in visibleListeners"
            :key="l.hash"
            :position="[l.location.lat, l.location.lon]"
        >
            {{ $gettext('IP') }}
            : {{ l.ip }}<br>
            {{ $gettext('Country') }}
            : {{ l.location.country }}<br>
            {{ $gettext('Region') }}
            : {{ l.location.region }}<br>
            {{ $gettext('City') }}
            : {{ l.location.city }}<br>
            {{ $gettext('Time') }}
            : {{ l.connected_time }}<br>
            {{ $gettext('User Agent') }}
            : {{ l.user_agent }}
        </map-point>
    </inner-map>
</template>

<script setup>
import InnerMap from "./InnerMap.vue";
import MapPoint from "./MapPoint.vue";
import {computed} from "vue";
import {filter} from "lodash";

const props = defineProps({
    listeners: {
        type: Array,
        default: () => {
            return [];
        }
    },
});

const visibleListeners = computed(() => {
    return filter(props.listeners, function (l) {
        return null !== l.location.lat && null !== l.location.lon;
    });
});
</script>

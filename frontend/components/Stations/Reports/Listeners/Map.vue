<template>
    <inner-map
        v-if="visibleListeners.length < 3000"
    >
        <template #default="{map}">
            <map-point
                v-for="l in visibleListeners"
                :key="l.hash"
                :map="map"
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
        </template>
    </inner-map>
</template>

<script setup lang="ts">
import InnerMap from "~/components/Stations/Reports/Listeners/InnerMap.vue";
import MapPoint from "~/components/Stations/Reports/Listeners/MapPoint.vue";
import {computed} from "vue";
import {filter} from "es-toolkit/compat";
import {ListenerRequired} from "~/entities/StationReports.ts";

const props = withDefaults(
    defineProps<{
        listeners: ListenerRequired[]
    }>(),
    {
        listeners: () => []
    }
);

type VisibleRow = Omit<ListenerRequired, 'location'> & {
    location: Omit<ListenerRequired["location"], 'lat' | 'lon'> & {
        lat: number,
        lon: number,
    }
};

const visibleListeners = computed<VisibleRow[]>(() => {
    return filter(props.listeners, function (l) {
        return null !== l.location.lat && null !== l.location.lon;
    }) as VisibleRow[];
});
</script>

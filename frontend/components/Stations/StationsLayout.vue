<template>
    <dashboard-with-sidebar>
        <template #sidebar>
            <loading lazy :loading="isLoading || isPlaceholderData">
                <sidebar/>
            </loading>
        </template>
        <template #default>
            <loading lazy :loading="isLoading || isPlaceholderData">
                <router-view/>
            </loading>
        </template>
    </dashboard-with-sidebar>
    <Teleport to="#station-time-wrapper">
        <div
            id="station-time"
            class="fs-6 d-none d-md-block"
            :title="$gettext('Station Time')"
        >
            <span class="mx-2">•</span>
            {{ name }}
            <span class="mx-2">&#x2022;</span>
            {{ clock }}
        </div>
    </Teleport>
    <Teleport to="#station-alerts-wrapper">
        <template v-if="userAllowedForStation(StationPermissions.Broadcasting)">
            <div
                v-if="!hasStarted"
                class="navdrawer-alert bg-success-subtle text-success-emphasis mb-5 p-2"
            >
                <router-link
                    :to="{name: 'stations:restart:index'}"
                >
                    <span class="fw-bold">{{ $gettext('Start Station') }}</span><br>
                    <small>
                        {{ $gettext('Ready to start broadcasting? Click to start your station.') }}
                    </small>
                </router-link>
            </div>
            <div
                v-else-if="needsRestart"
                class="navdrawer-alert bg-warning-subtle text-warning-emphasis mb-5 p-2"
            >
                <router-link
                    :to="{name: 'stations:restart:index'}"
                >
                    <span class="fw-bold">{{ $gettext('Reload to Apply Changes') }}</span><br>
                    <small>
                        {{ $gettext('Your station has changes that require a reload to apply.') }}
                    </small>
                </router-link>
            </div>
        </template>
    </Teleport>
</template>

<script setup lang="ts">
import Sidebar from "~/components/Stations/Sidebar.vue";
import {useStationData, useStationQuery} from "~/functions/useStationQuery.ts";
import Loading from "~/components/Common/Loading.vue";
import DashboardWithSidebar from "~/components/Layout/DashboardWithSidebar.vue";
import {useUserAllowedForStation} from "~/functions/useUserallowedForStation.ts";
import {toRefs, useIntervalFn} from "@vueuse/core";
import {useLuxon} from "~/vendor/luxon.ts";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {ref} from "vue";
import {StationPermissions} from "~/entities/ApiInterfaces.ts";

const {
    isLoading,
    isPlaceholderData
} = useStationQuery();

const {userAllowedForStation} = useUserAllowedForStation();

const stationData = useStationData();
const {name, hasStarted, needsRestart, timezone} = toRefs(stationData);

const {DateTime} = useLuxon();
const {now, formatDateTimeAsTime} = useStationDateTimeFormatter(timezone);

const clock = ref('');

useIntervalFn(() => {
    clock.value = formatDateTimeAsTime(now(), DateTime.TIME_WITH_SHORT_OFFSET);
}, 1000, {
    immediate: true,
    immediateCallback: true
});
</script>

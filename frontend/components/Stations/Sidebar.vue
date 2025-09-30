<template>
    <div class="navdrawer-header offcanvas-header">
        <div class="d-flex align-items-center">
            <router-link
                :to="{ name: 'stations:index' }"
                class="navbar-brand px-0 flex-fill"
            >
                {{ name }}
                <div
                    id="station-time"
                    class="fs-6"
                    :title="$gettext('Station Time')"
                >
                    {{ clock }}
                </div>
            </router-link>

            <router-link
                v-if="userAllowedForStation(StationPermissions.Profile)"
                :to="{ name: 'stations:profile:edit' }"
                class="navbar-brand ms-0 flex-shrink-0"
            >
                <icon-ic-edit/>
                <span class="visually-hidden">{{ $gettext('Edit Profile') }}</span>
            </router-link>
        </div>
    </div>

    <template v-if="userAllowedForStation(StationPermissions.Broadcasting)">
        <div
            v-if="!hasStarted"
            class="navdrawer-alert bg-success-subtle text-success-emphasis"
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
            class="navdrawer-alert bg-warning-subtle text-warning-emphasis"
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

    <div class="offcanvas-body">
        <sidebar-menu :menu="menuItems" />
    </div>
</template>

<script setup lang="ts">
import {ref} from "vue";
import SidebarMenu from "~/components/Common/SidebarMenu.vue";
import {toRefs, useIntervalFn} from "@vueuse/core";
import {useStationsMenu} from "~/components/Stations/menu";
import {userAllowedForStation} from "~/acl";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {useLuxon} from "~/vendor/luxon.ts";
import {StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import IconIcEdit from "~icons/ic/baseline-edit";

const menuItems = useStationsMenu();

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

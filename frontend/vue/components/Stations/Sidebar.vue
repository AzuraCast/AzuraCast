<template>
    <div class="navdrawer-header offcanvas-header">
        <div class="d-flex align-items-center">
            <router-link
                :to="{ name: 'stations:index' }"
                class="navbar-brand px-0 flex-fill"
            >
                <div>{{ name }}</div>
                <div
                    id="station-time"
                    class="fs-6"
                    :title="$gettext('Station Time')"
                >
                    {{ clock }}
                </div>
            </router-link>

            <router-link
                v-if="userAllowedForStation(StationPermission.Profile)"
                :to="{ name: 'stations:profile:edit' }"
                class="navbar-brand ms-0 flex-shrink-0"
            >
                <icon :icon="IconEdit" />
                <span class="visually-hidden">{{ $gettext('Edit Profile') }}</span>
            </router-link>
        </div>
    </div>

    <template v-if="userAllowedForStation(StationPermission.Broadcasting)">
        <div
            v-if="!station.hasStarted"
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
import Icon from "~/components/Common/Icon.vue";
import SidebarMenu from "~/components/Common/SidebarMenu.vue";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast";
import {useEventBus, useIntervalFn} from "@vueuse/core";
import {useStationsMenu} from "~/components/Stations/menu";
import {StationPermission, userAllowedForStation} from "~/acl";
import {useAxios} from "~/vendor/axios.ts";
import {getStationApiUrl} from "~/router.ts";
import {useLuxon} from "~/vendor/luxon.ts";
import {IconEdit} from "~/components/Common/icons.ts";

const props = defineProps({
    station: {
        type: Object,
        required: true
    }
});

const menuItems = useStationsMenu();

const {timeConfig} = useAzuraCast();
const {name, timezone} = useAzuraCastStation();
const {DateTime} = useLuxon();

const clock = ref('');

useIntervalFn(() => {
    clock.value = DateTime.now().setZone(timezone).toLocaleString({
        ...DateTime.TIME_WITH_SHORT_OFFSET,
        ...timeConfig
    })
}, 1000, {
    immediate: true,
    immediateCallback: true
});

const restartEventBus = useEventBus<boolean>('station-restart');
const restartStatusUrl = getStationApiUrl('/restart-status');
const needsRestart = ref(props.station.needsRestart);
const {axios} = useAxios();

restartEventBus.on((forceRestart: boolean): void => {
    if (forceRestart) {
        needsRestart.value = true;
    } else {
        axios.get(restartStatusUrl.value).then((resp) => {
            needsRestart.value = resp.data.needs_restart;
        });
    }
});
</script>

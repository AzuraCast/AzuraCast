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
                <icon :icon="IconEdit" />
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
import Icon from "~/components/Common/Icon.vue";
import SidebarMenu from "~/components/Common/SidebarMenu.vue";
import {useAzuraCastStation} from "~/vendor/azuracast";
import {useIntervalFn} from "@vueuse/core";
import {useStationsMenu} from "~/components/Stations/menu";
import {StationPermissions, userAllowedForStation} from "~/acl";
import {useAxios} from "~/vendor/axios.ts";
import {getStationApiUrl} from "~/router.ts";
import {IconEdit} from "~/components/Common/icons.ts";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {useLuxon} from "~/vendor/luxon.ts";
import {useRestartEventBus} from "~/functions/useMayNeedRestart.ts";
import {ApiStationRestartStatus} from "~/entities/ApiInterfaces.ts";

const menuItems = useStationsMenu();

const {name, hasStarted, needsRestart: initialNeedsRestart} = useAzuraCastStation();

const {DateTime} = useLuxon();
const {now, formatDateTimeAsTime} = useStationDateTimeFormatter();

const clock = ref('');

useIntervalFn(() => {
    clock.value = formatDateTimeAsTime(now(), DateTime.TIME_WITH_SHORT_OFFSET);
}, 1000, {
    immediate: true,
    immediateCallback: true
});

const restartEventBus = useRestartEventBus();
const restartStatusUrl = getStationApiUrl('/restart-status');
const needsRestart = ref<boolean>(initialNeedsRestart);
const {axios} = useAxios();

restartEventBus.on((forceRestart: boolean): void => {
    if (forceRestart) {
        needsRestart.value = true;
    } else {
        void axios.get<Required<ApiStationRestartStatus>>(restartStatusUrl.value).then(
            ({data}) => {
                needsRestart.value = data.needs_restart;
            }
        );
    }
});
</script>

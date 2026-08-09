<template>
    <card-page
        id="profile-backend"
        header-id="hdr_backend"
    >
        <template #header="{id}">
            <div class="d-flex align-items-center">
                <h3
                    :id="id"
                    class="flex-fill card-title my-0"
                >
                    {{ $gettext('AutoDJ Service') }}

                    <br>
                    <small>{{ backendName }}</small>
                </h3>
                <div class="flex-shrink-0">
                    <running-badge :running="profileData.services.backendRunning"/>
                </div>
            </div>
        </template>

        <template
            v-if="userAllowedForStation(StationPermissions.Broadcasting) && stationData.hasStarted"
            #footer_actions
        >
            <button
                type="button"
                class="btn btn-link text-secondary"
                @click="doRestart()"
            >
                <icon-ic-update/>

                <span>
                    {{ $gettext('Restart') }}
                </span>
            </button>
            <button
                v-if="!profileData.services.backendRunning"
                type="button"
                class="btn btn-link text-success"
                @click="doStart()"
            >
                <icon-ic-play-arrow/>
                <span>
                    {{ $gettext('Start') }}
                </span>
            </button>
            <button
                v-if="profileData.services.backendRunning"
                type="button"
                class="btn btn-link text-danger"
                @click="doStop()"
            >
                <icon-ic-stop/>
                <span>
                    {{ $gettext('Stop') }}
                </span>
            </button>
        </template>
    </card-page>
</template>

<script setup lang="ts">
import { computed } from "vue";
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import CardPage from "~/components/Common/CardPage.vue";
import useMakeApiCall from "~/components/Stations/Profile/useMakeApiCall.ts";
import { useStationProfileData } from "~/components/Stations/Profile/useProfileQuery.ts";
import {
    BackendAdapters,
    StationPermissions,
} from "~/entities/ApiInterfaces.ts";
import { useApiRouter } from "~/functions/useApiRouter.ts";
import { useStationData } from "~/functions/useStationQuery.ts";
import { useUserAllowedForStation } from "~/functions/useUserallowedForStation.ts";
import { useTranslate } from "~/vendor/gettext";
import IconIcPlayArrow from "~icons/ic/baseline-play-arrow";
import IconIcStop from "~icons/ic/baseline-stop";
import IconIcUpdate from "~icons/ic/baseline-update";

const stationData = useStationData();
const profileData = useStationProfileData();

const { userAllowedForStation } = useUserAllowedForStation();

const { getStationApiUrl } = useApiRouter();

const backendRestartUri = getStationApiUrl("/backend/restart");
const backendStartUri = getStationApiUrl("/backend/start");
const backendStopUri = getStationApiUrl("/backend/stop");

const backendName = computed(() => {
    if (stationData.value.backendType === BackendAdapters.Liquidsoap) {
        return "Liquidsoap";
    }
    return "";
});

const { $gettext } = useTranslate();

const doRestart = useMakeApiCall(backendRestartUri, {
    title: $gettext("Restart service?"),
    confirmButtonText: $gettext("Restart"),
});

const doStart = useMakeApiCall(backendStartUri, {
    title: $gettext("Start service?"),
    confirmButtonText: $gettext("Start"),
    confirmButtonClass: "btn-success",
});

const doStop = useMakeApiCall(backendStopUri, {
    title: $gettext("Stop service?"),
    confirmButtonText: $gettext("Stop"),
    confirmButtonClass: "btn-danger",
});
</script>

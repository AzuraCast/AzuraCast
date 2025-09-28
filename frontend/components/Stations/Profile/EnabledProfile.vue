<template>
    <profile-header/>

    <div
        id="profile"
        class="row row-of-cards"
    >
        <div class="col-lg-7">
            <template v-if="stationData.hasStarted">
                <profile-now-playing/>

                <profile-schedule/>

                <profile-streams/>
            </template>
            <template v-else>
                <now-playing-not-started-panel />
            </template>

            <profile-public-pages/>
        </div>

        <div class="col-lg-5">
            <profile-requests v-if="hasActiveBackend"/>

            <profile-streamers v-if="hasActiveBackend"/>

            <template v-if="hasActiveFrontend">
                <profile-frontend/>
            </template>

            <template v-if="hasActiveBackend">
                <profile-backend/>
            </template>
            <template v-else>
                <profile-backend-none />
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import ProfileStreams from "~/components/Stations/Profile/StreamsPanel.vue";
import ProfileHeader from "~/components/Stations/Profile/HeaderPanel.vue";
import ProfileNowPlaying from "~/components/Stations/Profile/NowPlayingPanel.vue";
import ProfileSchedule from "~/components/Stations/Profile/SchedulePanel.vue";
import ProfileRequests from "~/components/Stations/Profile/RequestsPanel.vue";
import ProfileStreamers from "~/components/Stations/Profile/StreamersPanel.vue";
import ProfilePublicPages from "~/components/Stations/Profile/PublicPagesPanel.vue";
import ProfileFrontend from "~/components/Stations/Profile/FrontendPanel.vue";
import ProfileBackendNone from "~/components/Stations/Profile/BackendNonePanel.vue";
import ProfileBackend from "~/components/Stations/Profile/BackendPanel.vue";
import NowPlayingNotStartedPanel from "~/components/Stations/Profile/NowPlayingNotStartedPanel.vue";
import {computed} from "vue";
import {BackendAdapters, FrontendAdapters} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";

const stationData = useStationData();

const hasActiveFrontend = computed(() => {
    return stationData.value.frontendType !== FrontendAdapters.Remote;
});

const hasActiveBackend = computed(() => {
    return stationData.value.backendType !== BackendAdapters.None;
});
</script>

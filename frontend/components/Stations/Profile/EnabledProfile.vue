<template>
    <profile-header
        v-bind="props"
        :station="profile.station"
    />

    <div
        id="profile"
        class="row row-of-cards"
    >
        <div class="col-lg-7">
            <template v-if="hasStarted">
                <profile-now-playing
                    v-bind="props"
                />

                <profile-schedule
                    :schedule-items="profile.schedule"
                />

                <profile-streams
                    :station="profile.station"
                />
            </template>
            <template v-else>
                <now-playing-not-started-panel />
            </template>

            <profile-public-pages
                v-bind="props"
            />
        </div>

        <div class="col-lg-5">
            <profile-requests
                v-if="stationSupportsRequests"
                v-bind="props"
            />

            <profile-streamers
                v-if="stationSupportsStreamers"
                v-bind="props"
            />

            <template v-if="hasActiveFrontend">
                <profile-frontend
                    v-bind="props"
                    :frontend-running="profile.services.frontend_running"
                />
            </template>

            <template v-if="hasActiveBackend">
                <profile-backend
                    v-bind="props"
                    :has-started="profile.services.station_has_started"
                    :backend-running="profile.services.backend_running"
                />
            </template>
            <template v-else>
                <profile-backend-none />
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import ProfileStreams from "~/components/Stations/Profile/StreamsPanel.vue";
import ProfileHeader, {ProfileHeaderPanelParentProps} from "~/components/Stations/Profile/HeaderPanel.vue";
import ProfileNowPlaying, {ProfileNowPlayingPanelProps} from "~/components/Stations/Profile/NowPlayingPanel.vue";
import ProfileSchedule from "~/components/Stations/Profile/SchedulePanel.vue";
import ProfileRequests, {ProfileRequestPanelProps} from "~/components/Stations/Profile/RequestsPanel.vue";
import ProfileStreamers, {ProfileStreamersPanelProps} from "~/components/Stations/Profile/StreamersPanel.vue";
import ProfilePublicPages, {ProfilePublicPagesPanelProps} from "~/components/Stations/Profile/PublicPagesPanel.vue";
import ProfileFrontend, {ProfileFrontendPanelParentProps} from "~/components/Stations/Profile/FrontendPanel.vue";
import ProfileBackendNone from "~/components/Stations/Profile/BackendNonePanel.vue";
import ProfileBackend, {ProfileBackendPanelParentProps} from "~/components/Stations/Profile/BackendPanel.vue";
import NowPlayingNotStartedPanel from "~/components/Stations/Profile/NowPlayingNotStartedPanel.vue";
import {computed} from "vue";
import {ApiStationProfile, BackendAdapters, FrontendAdapters} from "~/entities/ApiInterfaces.ts";
import {DeepRequired} from "utility-types";

export interface EnabledProfileProps extends ProfileBackendPanelParentProps,
    ProfileFrontendPanelParentProps,
    ProfileHeaderPanelParentProps,
    ProfileNowPlayingPanelProps,
    ProfileRequestPanelProps,
    ProfilePublicPagesPanelProps,
    ProfileStreamersPanelProps {
    stationSupportsRequests: boolean,
    stationSupportsStreamers: boolean,
    profile: DeepRequired<ApiStationProfile>
}

const props = defineProps<EnabledProfileProps>();

const hasActiveFrontend = computed(() => {
    return props.frontendType !== FrontendAdapters.Remote;
});

const hasActiveBackend = computed(() => {
    return props.backendType !== BackendAdapters.None;
});
</script>

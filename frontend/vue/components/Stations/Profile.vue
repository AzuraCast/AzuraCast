<template>
    <profile-header v-bind="$props" :np="np"></profile-header>

    <div class="row" id="profile">
        <div class="col-lg-7">
            <profile-now-playing v-bind="$props" :np="np"></profile-now-playing>

            <profile-schedule :station-time-zone="stationTimeZone" :schedule-items="np.schedule"></profile-schedule>

            <profile-streams v-bind="$props" :np="np"></profile-streams>

            <profile-public-pages v-bind="$props"></profile-public-pages>
        </div>

        <div class="col-lg-5">
            <profile-requests v-bind="$props" v-if="stationSupportsRequests"></profile-requests>

            <profile-streamers v-bind="$props" v-if="stationSupportsStreamers"></profile-streamers>

            <template v-if="hasActiveFrontend">
                <profile-frontend v-bind="$props" :np="np"></profile-frontend>
            </template>

            <template v-if="hasActiveBackend">
                <profile-backend v-bind="$props" :np="np"></profile-backend>
            </template>
            <template v-else>
                <profile-backend-none></profile-backend-none>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    inheritAttrs: false
};
</script>

<script setup>
import ProfileStreams from './Profile/StreamsPanel';
import ProfileHeader from './Profile/HeaderPanel';
import ProfileNowPlaying from './Profile/NowPlayingPanel';
import ProfileSchedule from './Profile/SchedulePanel';
import ProfileRequests from './Profile/RequestsPanel';
import ProfileStreamers from './Profile/StreamersPanel';
import ProfilePublicPages from './Profile/PublicPagesPanel';
import ProfileFrontend from './Profile/FrontendPanel';
import ProfileBackendNone from './Profile/BackendNonePanel';
import ProfileBackend from './Profile/BackendPanel';
import {BACKEND_NONE, FRONTEND_REMOTE} from '~/components/Entity/RadioAdapters';
import NowPlaying from '~/components/Entity/NowPlaying';
import {computed, onMounted, shallowRef} from "vue";
import {useAxios} from "~/vendor/axios";
import backendPanelProps from "./Profile/backendPanelProps";
import embedModalProps from "./Profile/embedModalProps";
import frontendPanelProps from "./Profile/frontendPanelProps";
import headerPanelProps from "./Profile/headerPanelProps";
import nowPlayingPanelProps from "./Profile/nowPlayingPanelProps";
import publicPagesPanelProps from "./Profile/publicPagesPanelProps";
import requestsPanelProps from "./Profile/requestsPanelProps";
import streamersPanelProps from "./Profile/streamersPanelProps";

const props = defineProps({
    ...backendPanelProps,
    ...embedModalProps,
    ...frontendPanelProps,
    ...headerPanelProps,
    ...nowPlayingPanelProps,
    ...publicPagesPanelProps,
    ...requestsPanelProps,
    ...streamersPanelProps,
    profileApiUri: String,
    stationTimeZone: String,
    stationSupportsRequests: Boolean,
    stationSupportsStreamers: Boolean
});

const np = shallowRef({
    ...NowPlaying,
    loading: true,
    services: {
        backend_running: false,
        frontend_running: false
    },
    schedule: []
});

const hasActiveFrontend = computed(() => {
    return props.frontendType !== FRONTEND_REMOTE;
});

const hasActiveBackend = computed(() => {
    return props.backendType !== BACKEND_NONE;
});

const {axios} = useAxios();

const checkNowPlaying = () => {
    axios.get(props.profileApiUri).then((response) => {
        let np_new = response.data;
        np_new.loading = false;

        np.value = np_new;

        setTimeout(checkNowPlaying, (!document.hidden) ? 15000 : 30000);
    }).catch((error) => {
        if (!error.response || error.response.data.code !== 403) {
            setTimeout(checkNowPlaying, (!document.hidden) ? 30000 : 120000);
        }
    });
}

onMounted(checkNowPlaying);
</script>

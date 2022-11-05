<template>
    <div>
        <profile-header v-bind="$props" :np="np"></profile-header>

        <div class="row" id="profile">
            <div class="col-lg-7">
                <profile-now-playing :np="np" v-bind="$props"></profile-now-playing>

                <profile-schedule :station-time-zone="stationTimeZone" :schedule-items="np.schedule"></profile-schedule>

                <profile-streams :np="np" v-bind="$props"></profile-streams>

                <profile-public-pages v-bind="$props"></profile-public-pages>
            </div>

            <div class="col-lg-5">
                <profile-requests v-if="stationSupportsRequests" v-bind="$props"></profile-requests>

                <profile-streamers v-if="stationSupportsStreamers" v-bind="$props"></profile-streamers>

                <template v-if="hasActiveFrontend">
                    <profile-frontend :np="np" v-bind="$props"></profile-frontend>
                </template>

                <template v-if="hasActiveBackend">
                    <profile-backend :np="np" v-bind="$props"></profile-backend>
                </template>
                <template v-else>
                    <profile-backend-none></profile-backend-none>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import ProfileStreams from './Profile/StreamsPanel';
import ProfileHeader, {profileHeaderProps} from './Profile/HeaderPanel';
import ProfileNowPlaying, {profileNowPlayingProps} from './Profile/NowPlayingPanel';
import ProfileSchedule from './Profile/SchedulePanel';
import ProfileRequests, {profileRequestsProps} from './Profile/RequestsPanel';
import ProfileStreamers, {profileStreamersProps} from './Profile/StreamersPanel';
import ProfilePublicPages, {profilePublicProps} from './Profile/PublicPagesPanel';
import ProfileFrontend, {profileFrontendProps} from './Profile/FrontendPanel';
import ProfileBackendNone from './Profile/BackendNonePanel';
import ProfileBackend, {profileBackendProps} from './Profile/BackendPanel';
import {profileEmbedModalProps} from './Profile/EmbedModal';
import {BACKEND_NONE, FRONTEND_REMOTE} from '~/components/Entity/RadioAdapters.js';
import NowPlaying from '~/components/Entity/NowPlaying';

export default {
    inheritAttrs: false,
    components: {
        ProfileBackend,
        ProfileBackendNone,
        ProfileFrontend,
        ProfilePublicPages,
        ProfileStreamers,
        ProfileRequests,
        ProfileSchedule,
        ProfileNowPlaying,
        ProfileHeader,
        ProfileStreams
    },
    mixins: [
        profileHeaderProps,
        profileNowPlayingProps,
        profileRequestsProps,
        profileStreamersProps,
        profilePublicProps,
        profileFrontendProps,
        profileBackendProps,
        profileEmbedModalProps
    ],
    props: {
        profileApiUri: String,
        stationTimeZone: String,
        stationSupportsRequests: Boolean,
        stationSupportsStreamers: Boolean
    },
    data() {
        return {
            np: {
                ...NowPlaying,
                loading: true,
                services: {
                    backend_running: false,
                    frontend_running: false
                },
                schedule: []
            }
        };
    },
    mounted() {
        this.checkNowPlaying();
    },
    computed: {
        hasActiveFrontend() {
            return this.frontendType !== FRONTEND_REMOTE;
        },
        hasActiveBackend() {
            return this.backendType !== BACKEND_NONE;
        },
    },
    methods: {
        checkNowPlaying() {
            this.axios.get(this.profileApiUri).then((response) => {
                let np = response.data;
                np.loading = false;
                this.np = np;

                setTimeout(this.checkNowPlaying, (!document.hidden) ? 15000 : 30000);
            }).catch((error) => {
                if (!error.response || error.response.data.code !== 403) {
                    setTimeout(this.checkNowPlaying, (!document.hidden) ? 30000 : 120000);
                }
            });
        }
    }
};
</script>

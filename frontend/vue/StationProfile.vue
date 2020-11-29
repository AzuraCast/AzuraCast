<template>
    <div class="row" id="profile">
        <div class="col-lg-7">
            <profile-header v-bind="$props"></profile-header>

            <profile-now-playing :np="np" v-bind="$props"></profile-now-playing>

            <profile-schedule :schedule-items="scheduleItems"></profile-schedule>

            <div class="row" v-if="stationSupportsRequests || stationSupportsStreamers">
                <div class="col" v-if="stationSupportsRequests">
                    <profile-requests v-bind="$props"></profile-requests>
                </div>
                <div class="col" v-if="stationSupportsStreamers">
                    <profile-streamers v-bind="$props"></profile-streamers>
                </div>
            </div>

            <profile-public-pages v-bind="$props"></profile-public-pages>
        </div>

        <div class="col-lg-5">
            <profile-streams :np="np" v-bind="$props"></profile-streams>

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
</template>

<script>
import ProfileStreams, { profileStreamsProps } from './station_profile/ProfileStreams';
import ProfileHeader, { profileHeaderProps } from './station_profile/ProfileHeader';
import ProfileNowPlaying, { profileNowPlayingProps } from './station_profile/ProfileNowPlaying';
import ProfileSchedule from './station_profile/ProfileSchedule';
import ProfileRequests, { profileRequestsProps } from './station_profile/ProfileRequests';
import ProfileStreamers, { profileStreamersProps } from './station_profile/ProfileStreamers';
import ProfilePublicPages, { profilePublicProps } from './station_profile/ProfilePublicPages';
import ProfileFrontend, { profileFrontendProps } from './station_profile/ProfileFrontend';
import ProfileBackendNone from './station_profile/ProfileBackendNone';
import ProfileBackend, { profileBackendProps } from './station_profile/ProfileBackend';
import { BACKEND_NONE, FRONTEND_REMOTE } from './inc/radio_adapters';
import axios from 'axios';

export default {
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
        profileStreamsProps,
        profileHeaderProps,
        profileNowPlayingProps,
        profileRequestsProps,
        profileStreamersProps,
        profilePublicProps,
        profileFrontendProps,
        profileBackendProps
    ],
    props: {
        profileApiUri: String,
        stationSupportsRequests: Boolean,
        stationSupportsStreamers: Boolean
    },
    data () {
        return {
            np: {
                loading: true,
                station: {
                    mounts: [],
                    remotes: []
                },
                services: {
                    backend_running: false,
                    frontend_running: false
                },
                now_playing: {
                    song: {
                        title: '',
                        artist: '',
                        art: false
                    },
                    playlist: '',
                    is_request: false,
                    duration: 0
                },
                listeners: {
                    current: 0,
                    unique: 0,
                    total: 0
                },
                live: {
                    is_live: false,
                    streamer_name: ''
                },
                playing_next: {
                    song: {
                        title: '',
                        artist: '',
                        art: false
                    },
                    playlist: ''
                },
                schedule: []
            },
            npTimeout: null
        };
    },
    mounted () {
        this.checkNowPlaying();
    },
    computed: {
        hasActiveFrontend () {
            return this.frontendType !== FRONTEND_REMOTE;
        },
        hasActiveBackend () {
            return this.backendType !== BACKEND_NONE;
        },
        scheduleItems () {
            let scheduleItems = this.np.schedule;
            let now = moment();

            scheduleItems.forEach(function (row, index) {
                let start_moment = moment.unix(row.start_timestamp);
                let end_moment = moment.unix(row.end_timestamp);

                this[index].time_until = start_moment.fromNow();

                if (start_moment.isSame(now, 'day')) {
                    this[index].start_formatted = start_moment.format('LT');
                } else {
                    this[index].start_formatted = start_moment.format('llll');
                }

                if (end_moment.isSame(start_moment, 'day')) {
                    this[index].end_formatted = end_moment.format('LT');
                } else {
                    this[index].end_formatted = end_moment.format('lll');
                }
            }, scheduleItems);

            return scheduleItems;
        }
    },
    methods: {
        checkNowPlaying () {
            axios.get(this.profileApiUri).then((response) => {
                let np = response.data;
                np.loading = false;

                this.np = np;
            }).catch((error) => {
                console.error(error);
                clearTimeout(this.np_timeout);
                this.npTimeout = setTimeout(this.checkNowPlaying, 30000);
            }).then(() => {
                clearTimeout(this.np_timeout);
                this.npTimeout = setTimeout(this.checkNowPlaying, 15000);
            });
        }
    }
};
</script>

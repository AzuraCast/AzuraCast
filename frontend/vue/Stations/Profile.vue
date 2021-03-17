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
import ProfileStreams from './Profile/StreamsPanel';
import ProfileHeader, { profileHeaderProps } from './Profile/HeaderPanel';
import ProfileNowPlaying, { profileNowPlayingProps } from './Profile/NowPlayingPanel';
import ProfileSchedule from './Profile/SchedulePanel';
import ProfileRequests, { profileRequestsProps } from './Profile/RequestsPanel';
import ProfileStreamers, { profileStreamersProps } from './Profile/StreamersPanel';
import ProfilePublicPages, { profilePublicProps } from './Profile/PublicPagesPanel';
import ProfileFrontend, { profileFrontendProps } from './Profile/FrontendPanel';
import ProfileBackendNone from './Profile/BackendNonePanel';
import ProfileBackend, { profileBackendProps } from './Profile/BackendPanel';
import { profileEmbedModalProps } from './Profile/EmbedModal';
import { BACKEND_NONE, FRONTEND_REMOTE } from '../Entity/RadioAdapters.js';
import NowPlaying from '../Entity/NowPlaying';
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
        stationSupportsRequests: Boolean,
        stationSupportsStreamers: Boolean
    },
    data () {
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

                Vue.nextTick(() => {
                    this.$eventHub.$emit('content_changed');
                });

                setTimeout(this.checkNowPlaying, 15000);
            }).catch((error) => {
                console.error(error);
                setTimeout(this.checkNowPlaying, 30000);
            });
        }
    }
};
</script>

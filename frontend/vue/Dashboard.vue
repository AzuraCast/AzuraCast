<template>
    <div id="dashboard">
        <section class="card mb-4" role="region">
            <div class="card-header bg-primary-dark d-flex flex-wrap align-items-center">
                <a class="flex-shrink-0" href="http://www.gravatar.com/" target="_blank">
                    <img :src="userAvatar" style="width: 64px; height: auto;" alt="">
                </a>
                <div class="flex-fill ml-3">
                    <h2 class="card-title mt-0">{{ userName }}</h2>
                    <h3 class="card-subtitle">{{ userEmail }}</h3>
                </div>
                <div class="flex-md-shrink-0 mt-3 mt-md-0">
                    <a class="btn btn-bg" role="button" :href="profileUrl">
                        <i class="material-icons" aria-hidden="true">account_circle</i>
                        <translate key="dashboard_btn_my_account">My Account</translate>
                    </a>
                    <a v-if="showAdmin" class="btn btn-bg ml-2" role="button" :href="adminUrl">
                        <i class="material-icons" aria-hidden="true">settings</i>
                        <translate key="dashboard_btn_administration">Administration</translate>
                    </a>
                </div>
            </div>

            <template v-if="!notificationsLoading && notifications.length > 0">
                <div v-for="notification in notifications" class="card-body d-flex" :class="'alert-'+notification.type" role="alert">
                    <div class="flex-shrink-0 mt-3 mr-3" v-if="'info' === notification.type">
                        <i class="material-icons lg" aria-hidden="true">info</i>
                    </div>
                    <div class="flex-shrink-0 mt-3 mr-3" v-else>
                        <i class="material-icons lg" aria-hidden="true">warning</i>
                    </div>
                    <div class="flex-fill">
                        <h4>{{ notification.title }}</h4>
                        <p class="card-text" v-html="notification.body"></p>
                    </div>
                </div>
            </template>
        </section>

        <section class="card mb-4" role="region" v-if="showCharts">
            <div class="card-header bg-primary-dark d-flex align-items-center">
                <div class="flex-fill">
                    <h3 class="card-title">
                        <translate key="dashboard_header_listeners_per_station">Listeners Per Station</translate>
                    </h3>
                </div>
                <div class="flex-shrink-0">
                    <b-button variant="outline-default" size="sm" class="py-2" v-b-toggle.charts>{{ langShowHideCharts }}</b-button>
                </div>
            </div>
            <b-collapse id="charts" v-model="chartsVisible">
                <b-overlay variant="card" :show="chartsLoading">
                    <div class="card-body py-5" v-if="chartsLoading">
                        &nbsp;
                    </div>
                    <b-tabs pills card lazy v-else>
                        <b-tab :title="langAverageListenersTab" active>
                            <time-series-chart style="width: 100%;" :data="chartsData.average.metrics">
                                <span v-html="chartsData.average.alt"></span>
                            </time-series-chart>
                        </b-tab>
                        <b-tab :title="langUniqueListenersTab">
                            <time-series-chart style="width: 100%;" :data="chartsData.unique.metrics">
                                <span v-html="chartsData.unique.alt"></span>
                            </time-series-chart>
                        </b-tab>
                    </b-tabs>
                </b-overlay>
            </b-collapse>
        </section>

        <section class="card" role="region">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    <translate key="dashboard_header_stations">Station Overview</translate>
                </h3>
            </div>
            <div class="card-actions" v-if="showAdmin">
                <a class="btn btn-outline-primary" :href="addStationUrl">
                    <i class="material-icons" aria-hidden="true">add</i>
                    <translate key="dashboard_btn_add_station">Add Station</translate>
                </a>
            </div>
            <data-table ref="datatable" id="station_playlists" paginated :fields="stationsFields" :responsive="false"
                        :api-url="stationsUrl">
                <template v-slot:cell(station)="{ item }">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 pr-2">
                            <a class="file-icon btn-audio" href="#" :data-url="item.station.listen_url"
                               @click.prevent="playAudio(item.station.listen_url)" :title="langPlayPause">
                                <i class="material-icons lg align-middle" aria-hidden="true">play_circle_filled</i>
                            </a>
                        </div>
                        <div class="flex-fill">
                            <big>{{ item.station.name }}</big><br>
                            <a :href="item.links.public" target="_blank">
                                <translate key="dashboard_link_public_page">Public Page</translate>
                            </a>
                        </div>
                    </div>
                </template>
                <template v-slot:cell(listeners)="{ item }">
                    <span class="nowplaying-listeners">{{ item.listeners.current }}</span>
                </template>
                <template v-slot:cell(now_playing)="{ item }">
                    <div v-if="item.now_playing.song.title != ''">
                        <strong><span class="nowplaying-title">{{ item.now_playing.song.title }}</span></strong><br>
                        <span class="nowplaying-artist">{{ item.now_playing.song.artist }}</span>
                    </div>
                    <div v-else>
                        <strong><span class="nowplaying-title">{{ item.now_playing.song.text }}</span></strong>
                    </div>
                </template>
                <template v-slot:cell(actions)="{ item }">
                    <a class="btn btn-primary" v-bind:href="item.links.manage">
                        <translate key="dashboard_btn_manage_station">Manage</translate>
                    </a>
                </template>
            </data-table>
        </section>
    </div>
</template>

<script>
import TimeSeriesChart from './components/TimeSeriesChart';
import DataTable from './components/DataTable';
import axios from 'axios';

export default {
    components: { DataTable, TimeSeriesChart },
    props: {
        userName: String,
        userEmail: String,
        userAvatar: String,
        profileUrl: String,
        adminUrl: String,
        showAdmin: Boolean,
        notificationsUrl: String,
        showCharts: Boolean,
        chartsUrl: String,
        addStationUrl: String,
        stationsUrl: String
    },
    data () {
        return {
            chartsLoading: true,
            chartsVisible: true,
            chartsData: {
                average: {
                    metrics: [],
                    alt: ''
                },
                unique: {
                    metrics: [],
                    alt: ''
                }
            },
            notificationsLoading: true,
            notifications: [],
            stationsTimeout: null,
            stationsFields: [
                {
                    key: 'station',
                    label: this.$gettext('Station Name'),
                    sortable: true
                },
                {
                    key: 'listeners',
                    label: this.$gettext('Listeners'),
                    sortable: true
                },
                { key: 'now_playing', label: this.$gettext('Now Playing'), sortable: false },
                { key: 'actions', label: this.$gettext('Actions'), sortable: false }
            ]
        };
    },
    computed: {
        langAverageListenersTab () {
            return this.$gettext('Average Listeners');
        },
        langUniqueListenersTab () {
            return this.$gettext('Unique Listeners');
        },
        langPlayPause () {
            return this.$gettext('Play/Pause');
        },
        langShowHideCharts () {
            if (this.chartsVisible) {
                return this.$gettext('Hide Charts');
            }
            return this.$gettext('Show Charts');
        }
    },
    mounted () {
        moment.tz.setDefault('UTC');

        axios.get(this.chartsUrl).then((response) => {
            this.chartsData = response.data;
            this.chartsLoading = false;
        }).catch((error) => {
            console.error(error);
        });

        axios.get(this.notificationsUrl).then((response) => {
            this.notifications = response.data;
            this.notificationsLoading = false;
        }).catch((error) => {
            console.error(error);
        });
    },
    methods: {
        playAudio (url) {
            this.$eventHub.$emit('player_toggle', url);
        }
    }
};
</script>

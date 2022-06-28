<template>
    <div id="dashboard">
        <section class="card mb-4" role="region">
            <div class="card-header bg-primary-dark d-flex flex-wrap align-items-center">
                <avatar class="flex-shrink-0 mr-3" v-if="user.avatar.url" :url="user.avatar.url"
                        :service="user.avatar.service" :service-url="user.avatar.serviceUrl"></avatar>

                <div class="flex-fill">
                    <h2 class="card-title mt-0">{{ user.name }}</h2>
                    <h3 class="card-subtitle">{{ user.email }}</h3>
                </div>

                <div class="flex-md-shrink-0 mt-3 mt-md-0">
                    <a class="btn btn-bg" role="button" :href="profileUrl">
                        <icon icon="account_circle"></icon>
                        <translate key="dashboard_btn_my_account">My Account</translate>
                    </a>
                    <a v-if="showAdmin" class="btn btn-bg ml-2" role="button" :href="adminUrl">
                        <icon icon="settings"></icon>
                        <translate key="dashboard_btn_administration">Administration</translate>
                    </a>
                </div>
            </div>

            <template v-if="!notificationsLoading && notifications.length > 0">
                <div v-for="notification in notifications" class="card-body d-flex align-items-center"
                     :class="'alert-'+notification.type" role="alert">
                    <div class="flex-shrink-0 mr-3" v-if="'info' === notification.type">
                        <icon class="lg" icon="info"></icon>
                    </div>
                    <div class="flex-shrink-0 mr-3" v-else>
                        <icon class="lg" icon="warning"></icon>
                    </div>
                    <div class="flex-fill">
                        <h4>{{ notification.title }}</h4>
                        <p class="card-text" v-html="notification.body"></p>
                    </div>
                    <div v-if="notification.actionLabel && notification.actionUrl" class="flex-shrink-0 ml-3">
                        <b-button :href="notification.actionUrl" target="_blank" size="sm" variant="light">
                            {{ notification.actionLabel }}
                        </b-button>
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
                    <b-button variant="outline-light" size="sm" class="py-2" @click="toggleCharts">{{
                            langShowHideCharts
                        }}
                    </b-button>
                </div>
            </div>
            <b-collapse id="charts" v-model="chartsVisible">
                <b-overlay variant="card" :show="chartsLoading">
                    <div class="card-body py-5" v-if="chartsLoading">
                        &nbsp;
                    </div>
                    <b-tabs pills card lazy v-else>
                        <b-tab active>
                            <template #title>
                                <translate key="tab_average_listeners">Average Listeners</translate>
                            </template>

                            <time-series-chart style="width: 100%;" :data="chartsData.average.metrics">
                                <span v-html="chartsData.average.alt"></span>
                            </time-series-chart>
                        </b-tab>
                        <b-tab>
                            <template #title>
                                <translate key="tab_unique_listeners">Unique Listeners</translate>
                            </template>

                            <time-series-chart style="width: 100%;" :data="chartsData.unique.metrics">
                                <span v-html="chartsData.unique.alt"></span>
                            </time-series-chart>
                        </b-tab>
                    </b-tabs>
                </b-overlay>
            </b-collapse>
        </section>

        <section class="card" role="region">
            <div class="card-header bg-primary-dark d-flex flex-wrap align-items-center">
                <div class="flex-fill">
                    <h2 class="card-title">
                        <translate key="dashboard_header_stations">Station Overview</translate>
                    </h2>
                </div>
                <div class="flex-shrink-0" v-if="showAdmin">
                    <b-button variant="outline-light" size="sm" class="py-2" :href="manageStationsUrl">
                        <icon icon="settings"></icon>
                        <translate key="dashboard_btn_manage_stations">Manage Stations</translate>
                    </b-button>
                </div>
            </div>

            <b-overlay variant="card" :show="stationsLoading">
                <div class="card-body py-3" v-if="stationsLoading">
                    &nbsp;
                </div>
                <table class="table table-striped table-responsive mb-0" id="station_dashboard" v-else>
                    <colgroup>
                        <col width="5%">
                        <col width="30%">
                        <col width="10%">
                        <col width="40%">
                        <col width="15%">
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="pr-3">&nbsp;</th>
                        <th class="pl-2">
                            <translate key="lang_col_station_name">Station Name</translate>
                        </th>
                        <th class="text-center">
                            <translate key="lang_col_listeners">Listeners</translate>
                        </th>
                        <th>
                            <translate key="lang_col_now_playing">Now Playing</translate>
                        </th>
                        <th class="text-right"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="align-middle" v-for="item in stations" :key="item.station.id">
                        <td class="text-center pr-3">
                            <play-button class="file-icon" icon-class="lg outlined align-middle"
                                         :url="item.station.listen_url" is-stream></play-button>
                        </td>
                        <td class="pl-2">
                            <big>{{ item.station.name }}</big><br>
                            <template v-if="item.station.is_public">
                                <a :href="item.links.public" target="_blank">
                                    <translate key="dashboard_link_public_page">Public Page</translate>
                                </a>
                            </template>
                        </td>
                        <td class="text-center">
                            <icon class="sm align-middle" icon="headset"></icon>
                            <template v-if="item.links.listeners">
                                <a :href="item.links.listeners">
                                    {{ item.listeners.total }}
                                </a>
                            </template>
                            <template v-else>
                                {{ item.listeners.total }}
                            </template>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <album-art v-if="showAlbumArt" :src="item.now_playing.song.art"
                                           class="flex-shrink-0 pr-3"></album-art>

                                <div v-if="!item.is_online" class="flex-fill text-muted">
                                    <translate key="station_offline">Station Offline</translate>
                                </div>
                                <div v-else-if="item.now_playing.song.title !== ''" class="flex-fill">
                                    <strong><span class="nowplaying-title">
                                        {{ item.now_playing.song.title }}
                                    </span></strong><br>
                                    <span class="nowplaying-artist">{{ item.now_playing.song.artist }}</span>
                                </div>
                                <div v-else class="flex-fill">
                                    <strong><span class="nowplaying-title">
                                        {{ item.now_playing.song.text }}
                                    </span></strong>
                                </div>
                            </div>
                        </td>
                        <td class="text-right">
                            <a class="btn btn-primary" v-bind:href="item.links.manage">
                                <translate key="dashboard_btn_manage_station">Manage</translate>
                            </a>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </b-overlay>
        </section>
    </div>
</template>

<script>
import TimeSeriesChart from '~/components/Common/TimeSeriesChart';
import DataTable from '~/components/Common/DataTable';
import store from 'store';
import Icon from '~/components/Common/Icon';
import Avatar from '~/components/Common/Avatar';
import PlayButton from "~/components/Common/PlayButton";
import AlbumArt from "~/components/Common/AlbumArt";

export default {
    components: {PlayButton, Avatar, Icon, DataTable, TimeSeriesChart, AlbumArt},
    props: {
        userUrl: String,
        profileUrl: String,
        adminUrl: String,
        showAdmin: Boolean,
        notificationsUrl: String,
        showCharts: Boolean,
        chartsUrl: String,
        manageStationsUrl: String,
        stationsUrl: String,
        showAlbumArt: Boolean
    },
    data() {
        return {
            userLoading: true,
            user: {
                name: this.$gettext('AzuraCast User'),
                email: null,
                avatar: {
                    url: null,
                    service: null,
                    serviceUrl: null
                },
            },
            chartsLoading: true,
            chartsVisible: null,
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
            stationsLoading: true,
            stations: []
        };
    },
    computed: {
        langShowHideCharts() {
            if (this.chartsVisible) {
                return this.$gettext('Hide Charts');
            }
            return this.$gettext('Show Charts');
        }
    },
    created() {
        if (store.enabled) {
            this.chartsVisible = store.get('dashboard_show_chart', true);
        } else {
            this.chartsVisible = true;
        }

        this.axios.get(this.userUrl).then((resp) => {
            this.user = {
                name: resp.data.name,
                email: resp.data.email,
                avatar: {
                    url: resp.data.avatar.url_64,
                    service: resp.data.avatar.service_name,
                    serviceUrl: resp.data.avatar.service_url
                }
            };
            this.userLoading = false;
        });

        if (this.showCharts) {
            this.axios.get(this.chartsUrl).then((response) => {
                this.chartsData = response.data;
                this.chartsLoading = false;
            });
        }

        this.axios.get(this.notificationsUrl).then((response) => {
            this.notifications = response.data;
            this.notificationsLoading = false;
        });

        this.updateNowPlaying();
    },
    methods: {
        toggleCharts() {
            this.chartsVisible = !this.chartsVisible;

            if (store.enabled) {
                store.set('dashboard_show_chart', this.chartsVisible);
            }
        },
        updateNowPlaying() {
            this.axios.get(this.stationsUrl).then((response) => {
                this.stationsLoading = false;
                this.stations = response.data;

                setTimeout(this.updateNowPlaying, (!document.hidden) ? 15000 : 30000);
            }).catch((error) => {
                if (!error.response || error.response.data.code !== 403) {
                    setTimeout(this.updateNowPlaying, (!document.hidden) ? 30000 : 120000);
                }
            });
        }
    }
};
</script>

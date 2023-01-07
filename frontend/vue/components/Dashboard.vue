<template>
    <div id="dashboard">
        <section
            class="card mb-4"
            role="region"
        >
            <div class="card-header bg-primary-dark d-flex flex-wrap align-items-center">
                <avatar
                    v-if="user.avatar.url"
                    class="flex-shrink-0 mr-3"
                    :url="user.avatar.url"
                    :service="user.avatar.service"
                    :service-url="user.avatar.serviceUrl"
                />

                <div class="flex-fill">
                    <h2 class="card-title mt-0">
                        {{ user.name }}
                    </h2>
                    <h3 class="card-subtitle">
                        {{ user.email }}
                    </h3>
                </div>

                <div class="flex-md-shrink-0 mt-3 mt-md-0 buttons">
                    <a
                        class="btn btn-bg"
                        role="button"
                        :href="profileUrl"
                    >
                        <icon icon="account_circle" />
                        {{ $gettext('My Account') }}
                    </a>
                    <a
                        v-if="showAdmin"
                        class="btn btn-bg"
                        role="button"
                        :href="adminUrl"
                    >
                        <icon icon="settings" />
                        {{ $gettext('Administration') }}
                    </a>
                </div>
            </div>

            <template v-if="!notificationsLoading && notifications.length > 0">
                <div
                    v-for="notification in notifications"
                    :key="notification.title"
                    class="card-body d-flex align-items-center"
                    :class="'alert-'+notification.type"
                    role="alert"
                >
                    <div
                        v-if="'info' === notification.type"
                        class="flex-shrink-0 mr-3"
                    >
                        <icon
                            class="lg"
                            icon="info"
                        />
                    </div>
                    <div
                        v-else
                        class="flex-shrink-0 mr-3"
                    >
                        <icon
                            class="lg"
                            icon="warning"
                        />
                    </div>
                    <div class="flex-fill">
                        <h4>{{ notification.title }}</h4>
                        <p
                            class="card-text"
                            v-html="notification.body"
                        />
                    </div>
                    <div
                        v-if="notification.actionLabel && notification.actionUrl"
                        class="flex-shrink-0 ml-3"
                    >
                        <b-button
                            :href="notification.actionUrl"
                            target="_blank"
                            size="sm"
                            variant="light"
                        >
                            {{ notification.actionLabel }}
                        </b-button>
                    </div>
                </div>
            </template>
        </section>

        <section
            v-if="showCharts"
            class="card mb-4"
            role="region"
        >
            <div class="card-header bg-primary-dark d-flex align-items-center">
                <div class="flex-fill">
                    <h3 class="card-title">
                        {{ $gettext('Listeners Per Station') }}
                    </h3>
                </div>
                <div class="flex-shrink-0">
                    <b-button
                        variant="outline-light"
                        size="sm"
                        class="py-2"
                        @click="chartsVisible = !chartsVisible"
                    >
                        {{
                            langShowHideCharts
                        }}
                    </b-button>
                </div>
            </div>
            <b-collapse
                id="charts"
                v-model="chartsVisible"
            >
                <b-overlay
                    variant="card"
                    :show="chartsLoading"
                >
                    <div
                        v-if="chartsLoading"
                        class="card-body py-5"
                    >
                        &nbsp;
                    </div>
                    <b-tabs
                        v-else
                        pills
                        card
                        lazy
                    >
                        <b-tab active>
                            <template #title>
                                {{ $gettext('Average Listeners') }}
                            </template>

                            <time-series-chart
                                style="width: 100%;"
                                :data="chartsData.average.metrics"
                            >
                                <span v-html="chartsData.average.alt" />
                            </time-series-chart>
                        </b-tab>
                        <b-tab>
                            <template #title>
                                {{ $gettext('Unique Listeners') }}
                            </template>

                            <time-series-chart
                                style="width: 100%;"
                                :data="chartsData.unique.metrics"
                            >
                                <span v-html="chartsData.unique.alt" />
                            </time-series-chart>
                        </b-tab>
                    </b-tabs>
                </b-overlay>
            </b-collapse>
        </section>

        <section
            class="card"
            role="region"
        >
            <div class="card-header bg-primary-dark d-flex flex-wrap align-items-center">
                <div class="flex-fill">
                    <h2 class="card-title">
                        {{ $gettext('Station Overview') }}
                    </h2>
                </div>
                <div
                    v-if="showAdmin"
                    class="flex-shrink-0"
                >
                    <b-button
                        variant="outline-light"
                        size="sm"
                        class="py-2"
                        :href="manageStationsUrl"
                    >
                        <icon icon="settings" />
                        {{ $gettext('Manage Stations') }}
                    </b-button>
                </div>
            </div>

            <b-overlay
                variant="card"
                :show="stationsLoading"
            >
                <div
                    v-if="stationsLoading"
                    class="card-body py-3"
                >
                    &nbsp;
                </div>
                <table
                    v-else
                    id="station_dashboard"
                    class="table table-striped table-responsive mb-0"
                >
                    <colgroup>
                        <col width="5%">
                        <col width="30%">
                        <col width="10%">
                        <col width="40%">
                        <col width="15%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="pr-3">
&nbsp;
                            </th>
                            <th class="pl-2">
                                {{ $gettext('Station Name') }}
                            </th>
                            <th class="text-center">
                                {{ $gettext('Listeners') }}
                            </th>
                            <th>{{ $gettext('Now Playing') }}</th>
                            <th class="text-right" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in stations"
                            :key="item.station.id"
                            class="align-middle"
                        >
                            <td class="text-center pr-3">
                                <play-button
                                    class="file-icon"
                                    icon-class="lg outlined align-middle"
                                    :url="item.station.listen_url"
                                    is-stream
                                />
                            </td>
                            <td class="pl-2">
                                <div class="typography-subheading">
                                    {{ item.station.name }}
                                </div>
                                <div v-if="item.station.is_public">
                                    <a
                                        :href="item.links.public"
                                        target="_blank"
                                    >
                                        {{ $gettext('Public Page') }}
                                    </a>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="pr-1">
                                    <icon
                                        class="sm align-middle"
                                        icon="headset"
                                    />
                                </span>
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
                                    <album-art
                                        v-if="showAlbumArt"
                                        :src="item.now_playing.song.art"
                                        class="flex-shrink-0 pr-3"
                                    />

                                    <div
                                        v-if="!item.is_online"
                                        class="flex-fill text-muted"
                                    >
                                        {{ $gettext('Station Offline') }}
                                    </div>
                                    <div
                                        v-else-if="item.now_playing.song.title !== ''"
                                        class="flex-fill"
                                    >
                                        <strong><span class="nowplaying-title">
                                            {{ item.now_playing.song.title }}
                                        </span></strong><br>
                                        <span class="nowplaying-artist">{{ item.now_playing.song.artist }}</span>
                                    </div>
                                    <div
                                        v-else
                                        class="flex-fill"
                                    >
                                        <strong><span class="nowplaying-title">
                                            {{ item.now_playing.song.text }}
                                        </span></strong>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right">
                                <a
                                    class="btn btn-primary"
                                    :href="item.links.manage"
                                >
                                    {{ $gettext('Manage') }}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </b-overlay>
        </section>
    </div>
</template>

<script setup>
import TimeSeriesChart from '~/components/Common/Charts/TimeSeriesChart.vue';
import Icon from '~/components/Common/Icon';
import Avatar from '~/components/Common/Avatar';
import PlayButton from "~/components/Common/PlayButton";
import AlbumArt from "~/components/Common/AlbumArt";
import {useAxios} from "~/vendor/axios";
import {useAsyncState, useIntervalFn, useStorage} from "@vueuse/core";
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState";

const props = defineProps({
    userUrl: {
        type: String,
        required: true
    },
    profileUrl: {
        type: String,
        required: true
    },
    adminUrl: {
        type: String,
        required: true
    },
    showAdmin: {
        type: Boolean,
        required: true
    },
    notificationsUrl: {
        type: String,
        required: true
    },
    showCharts: {
        type: Boolean,
        required: true
    },
    chartsUrl: {
        type: String,
        required: true
    },
    manageStationsUrl: {
        type: String,
        required: true
    },
    stationsUrl: {
        type: String,
        required: true
    },
    showAlbumArt: {
        type: Boolean,
        required: true
    }
});

const chartsVisible = useStorage('dashboard_show_chart', true);

const {$gettext} = useTranslate();

const langShowHideCharts = computed(() => {
    return (chartsVisible.value)
        ? $gettext('Hide Charts')
        : $gettext('Show Charts')
});

const {axios} = useAxios();

const {state: user} = useAsyncState(
    () => axios.get(props.userUrl)
        .then((resp) => {
            return {
                name: resp.data.name,
                email: resp.data.email,
                avatar: {
                    url: resp.data.avatar.url_64,
                    service: resp.data.avatar.service_name,
                    serviceUrl: resp.data.avatar.service_url
                }
            };
        }),
    {
        name: $gettext('AzuraCast User'),
        email: null,
        avatar: {
            url: null,
            service: null,
            serviceUrl: null
        },
    }
);

const {state: chartsData, isLoading: chartsLoading} = useAsyncState(
    () => axios.get(props.chartsUrl).then((r) => r.data),
    {
        average: {
            metrics: [],
            alt: ''
        },
        unique: {
            metrics: [],
            alt: ''
        }
    }
);

const {state: notifications, isLoading: notificationsLoading} = useAsyncState(
    () => axios.get(props.notificationsUrl).then((r) => r.data),
    []
);

const {state: stations, isLoading: stationsLoading, execute: reloadStations} = useRefreshableAsyncState(
    () => axios.get(props.stationsUrl).then((r) => r.data),
    [],
);

const stationsReloadTimeout = computed(() => {
    return (!document.hidden) ? 15000 : 30000
});

useIntervalFn(
    reloadStations,
    stationsReloadTimeout
);
</script>

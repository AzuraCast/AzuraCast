<template>
    <div
        id="dashboard"
        class="row-of-cards"
    >
        <section
            class="card"
            role="region"
            :aria-label="$gettext('Account Details')"
        >
            <div class="card-header text-bg-primary d-flex flex-wrap align-items-center">
                <avatar
                    v-if="user.avatar.url"
                    class="flex-shrink-0 me-3"
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
                        class="btn btn-dark btn-lg"
                        role="button"
                        :href="profileUrl"
                    >
                        <icon icon="account_circle" />
                        <span>{{ $gettext('My Account') }}</span>
                    </a>
                    <a
                        v-if="showAdmin"
                        class="btn btn-dark btn-lg"
                        role="button"
                        :href="adminUrl"
                    >
                        <icon icon="settings" />
                        <span>{{ $gettext('Administration') }}</span>
                    </a>
                </div>
            </div>

            <template v-if="!notificationsLoading && notifications.length > 0">
                <div
                    v-for="notification in notifications"
                    :key="notification.title"
                    class="card-body d-flex align-items-center alert"
                    :class="'alert-'+notification.type"
                    role="alert"
                    aria-live="polite"
                >
                    <div
                        v-if="'info' === notification.type"
                        class="flex-shrink-0 me-3"
                    >
                        <icon
                            class="lg"
                            icon="info"
                        />
                    </div>
                    <div
                        v-else
                        class="flex-shrink-0 me-3"
                    >
                        <icon
                            class="lg"
                            icon="warning"
                        />
                    </div>
                    <div class="flex-fill">
                        <h4>{{ notification.title }}</h4>
                        <p class="card-text">
                            {{ notification.body }}
                        </p>
                    </div>
                    <div
                        v-if="notification.actionLabel && notification.actionUrl"
                        class="flex-shrink-0 ms-3"
                    >
                        <a
                            class="btn btn-sm"
                            :class="'btn-'+notification.type"
                            :href="notification.actionUrl"
                            target="_blank"
                        >
                            {{ notification.actionLabel }}
                        </a>
                    </div>
                </div>
            </template>
        </section>

        <section
            v-if="showCharts"
            class="card"
            role="region"
            aria-labelledby="hdr_listeners_per_station"
        >
            <div class="card-header text-bg-primary d-flex align-items-center">
                <div class="flex-fill">
                    <h3
                        id="hdr_listeners_per_station"
                        class="card-title"
                    >
                        {{ $gettext('Listeners Per Station') }}
                    </h3>
                </div>
                <div class="flex-shrink-0">
                    <button
                        class="btn btn-sm btn-dark py-2"
                        @click="chartsVisible = !chartsVisible"
                    >
                        {{
                            langShowHideCharts
                        }}
                    </button>
                </div>
            </div>
            <div
                id="charts"
                class="card-body collapse collapse-vertical"
                :class="(chartsVisible) ? 'show' : ''"
            >
                <dashboard-charts
                    v-if="chartsVisible"
                    :charts-url="chartsUrl"
                />
            </div>
        </section>

        <section
            class="card"
            role="region"
            aria-labelledby="hdr_stations"
        >
            <div class="card-header text-bg-primary d-flex flex-wrap align-items-center">
                <div class="flex-fill">
                    <h2
                        id="hdr_stations"
                        class="card-title"
                    >
                        {{ $gettext('Station Overview') }}
                    </h2>
                </div>
                <div
                    v-if="showAdmin"
                    class="flex-shrink-0"
                >
                    <a
                        class="btn btn-dark py-2"
                        :href="manageStationsUrl"
                    >
                        <icon icon="settings" />
                        <span>
                            {{ $gettext('Manage Stations') }}
                        </span>
                    </a>
                </div>
            </div>

            <loading :loading="stationsLoading">
                <table
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
                            <th class="pe-3">
                            &nbsp;
                            </th>
                            <th class="ps-2">
                                {{ $gettext('Station Name') }}
                            </th>
                            <th class="text-center">
                                {{ $gettext('Listeners') }}
                            </th>
                            <th>{{ $gettext('Now Playing') }}</th>
                            <th class="text-end" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in stations"
                            :key="item.station.id"
                            class="align-middle"
                        >
                            <td class="text-center pe-3">
                                <play-button
                                    class="file-icon"
                                    icon-class="lg outlined align-middle"
                                    :url="item.station.listen_url"
                                    is-stream
                                />
                            </td>
                            <td class="ps-2">
                                <div class="h5 m-0">
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
                                <span class="pe-1">
                                    <icon
                                        class="sm align-middle"
                                        icon="headset"
                                    />
                                </span>
                                <template v-if="item.links.listeners">
                                    <a
                                        :href="item.links.listeners"
                                        :aria-label="$gettext('View Listener Report')"
                                    >
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
                                        class="flex-shrink-0 pe-3"
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
                            <td class="text-end">
                                <a
                                    class="btn btn-primary"
                                    :href="item.links.manage"
                                    role="button"
                                >
                                    {{ $gettext('Manage') }}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </loading>
        </section>
    </div>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import Avatar from '~/components/Common/Avatar';
import PlayButton from "~/components/Common/PlayButton";
import AlbumArt from "~/components/Common/AlbumArt";
import {useAxios} from "~/vendor/axios";
import {useAsyncState, useIntervalFn, useLocalStorage} from "@vueuse/core";
import {computed} from "vue";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState";
import DashboardCharts from "~/components/DashboardCharts.vue";
import {useTranslate} from "~/vendor/gettext";
import Loading from "~/components/Common/Loading.vue";

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

const chartsVisible = useLocalStorage('dashboard_show_chart', true);

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

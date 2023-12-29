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
            <user-info-panel>
                <a
                    class="btn btn-dark"
                    role="button"
                    :href="profileUrl"
                >
                    <icon :icon="IconAccountCircle" />
                    <span>{{ $gettext('My Account') }}</span>
                </a>
                <a
                    v-if="showAdmin"
                    class="btn btn-dark"
                    role="button"
                    :href="adminUrl"
                >
                    <icon :icon="IconSettings" />
                    <span>{{ $gettext('Administration') }}</span>
                </a>
            </user-info-panel>

            <template v-if="!notificationsLoading && notifications.length > 0">
                <div
                    v-for="notification in notifications"
                    :key="notification.title"
                    class="card-body d-flex align-items-center alert flex-md-row flex-column"
                    :class="'alert-'+notification.type"
                    role="alert"
                    aria-live="polite"
                >
                    <div
                        v-if="'info' === notification.type"
                        class="flex-shrink-0 me-3"
                    >
                        <icon
                            :icon="IconInfo"
                            class="lg"
                        />
                    </div>
                    <div
                        v-else
                        class="flex-shrink-0 me-3"
                    >
                        <icon
                            :icon="IconWarning"
                            class="lg"
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
                        class="flex-shrink-0 ms-md-3 mt-3 mt-md-0"
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

        <card-page
            v-if="showCharts"
            header-id="hdr_listeners_per_station"
        >
            <template #header="{id}">
                <div class="d-flex align-items-center">
                    <div class="flex-fill">
                        <h3
                            :id="id"
                            class="card-title"
                        >
                            {{ $gettext('Listeners Per Station') }}
                        </h3>
                    </div>
                    <div class="flex-shrink-0">
                        <button
                            type="button"
                            class="btn btn-sm btn-dark py-2"
                            @click="chartsVisible = !chartsVisible"
                        >
                            {{
                                langShowHideCharts
                            }}
                        </button>
                    </div>
                </div>
            </template>

            <div
                v-if="chartsVisible"
                id="charts"
                class="card-body"
            >
                <dashboard-charts :charts-url="chartsUrl" />
            </div>
        </card-page>

        <card-page header-id="hdr_stations">
            <template #header="{id}">
                <div class="d-flex flex-wrap align-items-center">
                    <div class="flex-fill">
                        <h2
                            :id="id"
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
                            <icon :icon="IconSettings" />
                            <span>
                                {{ $gettext('Manage Stations') }}
                            </span>
                        </a>
                    </div>
                </div>
            </template>

            <loading :loading="stationsLoading">
                <div class="table-responsive">
                    <table
                        id="station_dashboard"
                        class="table table-striped"
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
                                <td class="text-center pe-1">
                                    <play-button
                                        class="file-icon btn-lg"
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
                                            :icon="IconHeadphones"
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
                </div>
            </loading>
        </card-page>
    </div>

    <header-inline-player />

    <lightbox ref="$lightbox" />
</template>

<script setup lang="ts">
import Icon from '~/components/Common/Icon.vue';
import PlayButton from "~/components/Common/PlayButton.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {useAxios} from "~/vendor/axios";
import {useAsyncState} from "@vueuse/core";
import {computed, ref} from "vue";
import DashboardCharts from "~/components/DashboardCharts.vue";
import {useTranslate} from "~/vendor/gettext";
import Loading from "~/components/Common/Loading.vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import CardPage from "~/components/Common/CardPage.vue";
import HeaderInlinePlayer from "~/components/HeaderInlinePlayer.vue";
import {LightboxTemplateRef, useProvideLightbox} from "~/vendor/lightbox";
import useOptionalStorage from "~/functions/useOptionalStorage";
import {IconAccountCircle, IconHeadphones, IconInfo, IconSettings, IconWarning} from "~/components/Common/icons";
import UserInfoPanel from "~/components/Account/UserInfoPanel.vue";
import {getApiUrl} from "~/router.ts";
import useAutoRefreshingAsyncState from "~/functions/useAutoRefreshingAsyncState.ts";

const props = defineProps({
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
    showCharts: {
        type: Boolean,
        required: true
    },
    manageStationsUrl: {
        type: String,
        required: true
    },
    showAlbumArt: {
        type: Boolean,
        required: true
    }
});

const notificationsUrl = getApiUrl('/frontend/dashboard/notifications');
const chartsUrl = getApiUrl('/frontend/dashboard/charts');
const stationsUrl = getApiUrl('/frontend/dashboard/stations');

const chartsVisible = useOptionalStorage<boolean>('dashboard_show_chart', true);

const {$gettext} = useTranslate();

const langShowHideCharts = computed(() => {
    return (chartsVisible.value)
        ? $gettext('Hide Charts')
        : $gettext('Show Charts')
});

const {axios, axiosSilent} = useAxios();

const {state: notifications, isLoading: notificationsLoading} = useAsyncState(
    () => axios.get(notificationsUrl.value).then((r) => r.data),
    []
);

const {state: stations, isLoading: stationsLoading} = useAutoRefreshingAsyncState(
    () => axiosSilent.get(stationsUrl.value).then((r) => r.data),
    [],
    {
        timeout: 15000
    }
);

const $lightbox = ref<LightboxTemplateRef>(null);
useProvideLightbox($lightbox);
</script>

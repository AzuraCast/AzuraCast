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

            <data-table
                id="dashboard_stations"
                ref="$datatable"
                :fields="stationFields"
                :api-url="stationsUrl"
                paginated
                responsive
                show-toolbar
                :hide-on-loading="false"
            >
                <template #cell(play_button)="{ item }">
                    <play-button
                        class="file-icon btn-lg"
                        :url="item.station.listen_url"
                        is-stream
                    />
                </template>
                <template #cell(name)="{ item }">
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
                </template>
                <template #cell(listeners)="{ item }">
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
                </template>
                <template #cell(now_playing)="{ item }">
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
                </template>
                <template #cell(actions)="{ item }">
                    <a
                        class="btn btn-primary"
                        :href="item.links.manage"
                        role="button"
                    >
                        {{ $gettext('Manage') }}
                    </a>
                </template>
            </data-table>
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
import {useAsyncState, useIntervalFn} from "@vueuse/core";
import {computed, ref} from "vue";
import DashboardCharts from "~/components/DashboardCharts.vue";
import {useTranslate} from "~/vendor/gettext";
import Lightbox from "~/components/Common/Lightbox.vue";
import CardPage from "~/components/Common/CardPage.vue";
import HeaderInlinePlayer from "~/components/HeaderInlinePlayer.vue";
import {LightboxTemplateRef, useProvideLightbox} from "~/vendor/lightbox";
import useOptionalStorage from "~/functions/useOptionalStorage";
import {IconAccountCircle, IconHeadphones, IconInfo, IconSettings, IconWarning} from "~/components/Common/icons";
import UserInfoPanel from "~/components/Account/UserInfoPanel.vue";
import {getApiUrl} from "~/router.ts";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable.ts";

const props = defineProps<{
    profileUrl: string,
    adminUrl: string,
    showAdmin: boolean,
    showCharts: boolean,
    manageStationsUrl: string,
    showAlbumArt: boolean,
}>();

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

const {axios} = useAxios();

const {state: notifications, isLoading: notificationsLoading} = useAsyncState(
    () => axios.get(notificationsUrl.value).then((r) => r.data),
    []
);

const stationFields: DataTableField[] = [
    {
        key: 'play_button',
        label: '',
        sortable: false,
        class: 'shrink'
    },
    {
        key: 'name',
        label: $gettext('Station Name'),
        sortable: true,
    },
    {
        key: 'listeners',
        label: $gettext('Listeners'),
        sortable: true
    },
    {
        key: 'now_playing',
        label: $gettext('Now Playing'),
        sortable: true
    },
    {
        key: 'actions',
        label: '',
        sortable: false,
        class: 'shrink'
    }
];

const $datatable = ref<DataTableTemplateRef>(null);
const {refresh} = useHasDatatable($datatable);

useIntervalFn(
    refresh,
    computed(() => (document.hidden) ? 30000 : 15000)
);

const $lightbox = ref<LightboxTemplateRef>(null);
useProvideLightbox($lightbox);
</script>

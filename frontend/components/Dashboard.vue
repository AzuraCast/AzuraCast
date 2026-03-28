<template>
    <dashboard-no-sidebar>
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
                    <router-link
                        class="btn btn-dark"
                        role="button"
                        :to="{ name: 'profile:index'}"
                    >
                        <icon-ic-account-circle/>
                        <span>{{ $gettext('My Account') }}</span>
                    </router-link>
                    <router-link
                        v-if="showAdmin"
                        class="btn btn-dark"
                        role="button"
                        :to="{ name: 'admin:index' }"
                    >
                        <icon-ic-settings/>
                        <span>{{ $gettext('Administration') }}</span>
                    </router-link>
                </user-info-panel>

                <template v-if="!notificationsLoading && notifications && notifications.length > 0">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        :id="notification.id"
                        class="card-body d-flex align-items-center alert flex-md-row flex-column"
                        :class="'alert-'+notification.type"
                        role="alert"
                        aria-live="polite"
                    >
                        <div
                            v-if="'info' === notification.type"
                            class="flex-shrink-0 me-3"
                        >
                            <icon-ic-info class="lg"/>
                        </div>
                        <div
                            v-else
                            class="flex-shrink-0 me-3"
                        >
                            <icon-ic-warning class="lg"/>
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
                    <dashboard-charts :charts-url="chartsUrl"/>
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
                            <router-link
                                class="btn btn-dark py-2"
                                :to="{ name: 'admin:stations:index' }"
                            >
                                <icon-ic-settings/>

                                <span>
                                    {{ $gettext('Manage Stations') }}
                                </span>
                            </router-link>
                        </div>
                    </div>
                </template>

                <data-table
                    id="dashboard_stations"
                    :fields="stationFields"
                    :provider="stationsItemProvider"
                    paginated
                    responsive
                    show-toolbar
                    :hide-on-loading="false"
                >
                    <template #cell(play_button)="{ item }">
                        <play-button
                            class="file-icon btn-lg"
                            :stream="{
                                url: item.station.listen_url,
                                title: item.station.name,
                                isStream: true
                            }"
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
                            <icon-ic-headphones class="sm align-middle"/>
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
                                v-if="showAlbumArt && item.now_playing &&
                                    item.now_playing.song && item.now_playing.song.art"
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
                                v-else-if="item.now_playing?.song?.title !== ''"
                                class="flex-fill"
                            >
                                <strong><span class="nowplaying-title">
                                    {{ item.now_playing?.song?.title }}
                                </span></strong><br>
                                <span class="nowplaying-artist">{{ item.now_playing?.song?.artist }}</span>
                            </div>
                            <div
                                v-else
                                class="flex-fill"
                            >
                                <strong><span class="nowplaying-title">
                                    {{ item.now_playing?.song?.text }}
                                </span></strong>
                            </div>
                        </div>
                    </template>
                    <template #cell(actions)="{ item }">
                        <router-link
                            class="btn btn-primary"
                            :to="{
                                name: 'stations:index',
                                params: {
                                    station_id: item.station.id
                                }
                            }"
                            role="button"
                        >
                            {{ $gettext('Manage') }}
                        </router-link>
                    </template>
                </data-table>
            </card-page>
        </div>
    </dashboard-no-sidebar>
</template>

<script setup lang="ts">
import PlayButton from "~/components/Common/Audio/PlayButton.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {useAxios} from "~/vendor/axios";
import {computed} from "vue";
import DashboardCharts from "~/components/DashboardCharts.vue";
import {useTranslate} from "~/vendor/gettext";
import CardPage from "~/components/Common/CardPage.vue";
import useOptionalStorage from "~/functions/useOptionalStorage";
import UserInfoPanel from "~/components/Account/UserInfoPanel.vue";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {ApiNotification, ApiNowPlaying, GlobalPermissions, HasLinks} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useQuery} from "@tanstack/vue-query";
import {useAzuraCastDashboardGlobals} from "~/vendor/azuracast.ts";
import DashboardNoSidebar from "~/components/Layout/DashboardNoSidebar.vue";
import IconIcAccountCircle from "~icons/ic/baseline-account-circle";
import IconIcHeadphones from "~icons/ic/baseline-headphones";
import IconIcInfo from "~icons/ic/baseline-info";
import IconIcSettings from "~icons/ic/baseline-settings";
import IconIcWarning from "~icons/ic/baseline-warning";
import {useUserAllowed} from "~/functions/useUserAllowed.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {showCharts, showAlbumArt} = useAzuraCastDashboardGlobals();

const {userAllowed} = useUserAllowed();
const showAdmin = userAllowed(GlobalPermissions.View);

const {getApiUrl} = useApiRouter();

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

const {data: notifications, isLoading: notificationsLoading} = useQuery<ApiNotification[]>({
    queryKey: [
        QueryKeys.Dashboard,
        'notifications'
    ],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiNotification[]>(notificationsUrl.value, {signal});
        return data;
    },
});

type ApiDashboard = ApiNowPlaying & Required<HasLinks>;

const stationFields: DataTableField<ApiDashboard>[] = [
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

const stationsItemProvider = useApiItemProvider<ApiDashboard>(
    stationsUrl,
    [
        QueryKeys.Dashboard,
        'stations'
    ],
    {
        refetchInterval: 15 * 1000
    }
);
</script>

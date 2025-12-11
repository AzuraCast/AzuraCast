<template>
    <div class="row row-of-cards">
        <div class="col-md-8">
            <card-page header-id="hdr_streamer_accounts">
                <template #header="{id}">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2
                                :id="id"
                                class="card-title"
                            >
                                {{ $gettext('Streamer/DJ Accounts') }}
                            </h2>
                        </div>
                        <div class="col-md-6 text-end">
                            <loading :loading="propsLoading" lazy>
                                <stations-common-quota
                                    v-if="props && props.recordStreams"
                                    :quota-url="quotaUrl"
                                />
                                <time-zone v-else/>
                            </loading>
                        </div>
                    </div>
                </template>

                <div class="card-body">
                    <tabs
                        nav-tabs-class="nav-tabs"
                        content-class="mt-3"
                        destroy-on-hide
                    >
                        <tab :label="$gettext('Account List')">
                            <div class="card-body-flush">
                                <div class="card-body buttons">
                                    <add-button
                                        :text="$gettext('Add Streamer')"
                                        @click="doCreate"
                                    />
                                </div>

                                <data-table
                                    id="station_streamers"
                                    :fields="fields"
                                    :provider="listItemProvider"
                                >
                                    <template #cell(art)="row">
                                        <album-art :src="row.item.art" />
                                    </template>
                                    <template #cell(streamer_username)="row">
                                        <code>{{ row.item.streamer_username }}</code>
                                        <div>
                                            <span
                                                v-if="!row.item.is_active"
                                                class="badge text-bg-danger"
                                            >
                                                {{ $gettext('Disabled') }}
                                            </span>
                                        </div>
                                    </template>
                                    <template #cell(actions)="row">
                                        <div class="btn-group btn-group-sm">
                                            <button
                                                type="button"
                                                class="btn btn-primary"
                                                @click="doEdit(row.item.links.self)"
                                            >
                                                {{ $gettext('Edit') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                @click="doShowBroadcasts(row.item.id, row.item.links.broadcasts, row.item.links.broadcasts_batch)"
                                            >
                                                {{ $gettext('Broadcasts') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-danger"
                                                @click="doDelete(row.item.links.self)"
                                            >
                                                {{ $gettext('Delete') }}
                                            </button>
                                        </div>
                                    </template>
                                </data-table>
                            </div>
                        </tab>
                        <schedule-view-tab
                            ref="$scheduleTab"
                            :schedule-url="scheduleUrl"
                            @click="doCalendarClick"
                        />
                    </tabs>
                </div>
            </card-page>
        </div>
        <div class="col-md-4">
            <loading :loading="propsLoading" lazy>
                <connection-info v-if="props" v-bind="props"/>
            </loading>
        </div>

        <edit-modal
            ref="$editModal"
            :create-url="listUrl"
            :new-art-url="newArtUrl"
            @relist="() => relist()"
        />

        <broadcasts-modal ref="$broadcastsModal" />
    </div>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Stations/Streamers/EditModal.vue";
import BroadcastsModal from "~/components/Stations/Streamers/BroadcastsModal.vue";
import ConnectionInfo from "~/components/Stations/Streamers/ConnectionInfo.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import AddButton from "~/components/Common/AddButton.vue";
import TimeZone from "~/components/Stations/Common/TimeZone.vue";
import ScheduleViewTab from "~/components/Stations/Common/ScheduleViewTab.vue";
import {EventImpl} from "@fullcalendar/core/internal";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import StationsCommonQuota from "~/components/Stations/Common/Quota.vue";
import {ApiStationsVueStreamersProps, StorageLocationTypes} from "~/entities/ApiInterfaces.ts";
import {useAxios} from "~/vendor/axios.ts";
import {useQuery} from "@tanstack/vue-query";
import Loading from "~/components/Common/Loading.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();

const propsUrl = getStationApiUrl('/vue/streamers');
const listUrl = getStationApiUrl('/streamers');
const newArtUrl = getStationApiUrl('/streamers/art');
const scheduleUrl = getStationApiUrl('/streamers/schedule');

const quotaUrl = getStationApiUrl(`/quota/${StorageLocationTypes.StationRecordings}`);

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiStationsVueStreamersProps>({
    queryKey: queryKeyWithStation(
        [
            QueryKeys.StationSftpUsers,
            'props'
        ]
    ),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiStationsVueStreamersProps>(propsUrl.value, {signal});
        return data;
    }
});

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'art', label: $gettext('Art'), sortable: false, class: 'shrink pe-0'},
    {key: 'display_name', label: $gettext('Display Name'), sortable: true},
    {key: 'streamer_username', isRowHeader: true, label: $gettext('Username'), sortable: true},
    {key: 'comments', label: $gettext('Notes'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider(
    listUrl,
    queryKeyWithStation([
        QueryKeys.StationStreamers
    ])
);

const $scheduleTab = useTemplateRef('$scheduleTab');

const relist = () => {
    void listItemProvider.refresh();
    $scheduleTab.value?.refresh();
}

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const doCalendarClick = (event: EventImpl) => {
    doEdit(event.extendedProps.edit_url);
};

const $broadcastsModal = useTemplateRef('$broadcastsModal');

const doShowBroadcasts = (id: number, listUrl: string, batchUrl: string) => {
    $broadcastsModal.value?.open(id, listUrl, batchUrl);
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Streamer?'),
    () => relist()
);
</script>

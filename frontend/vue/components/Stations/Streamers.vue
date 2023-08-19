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
                        <div class="col-md-6 text-end text-muted">
                            {{
                                $gettext(
                                    'This station\'s time zone is currently %{tz}.',
                                    {tz: timezone}
                                )
                            }}
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
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        @click="doCreate"
                                    >
                                        <icon icon="add" />
                                        <span>
                                            {{ $gettext('Add Streamer') }}
                                        </span>
                                    </button>
                                </div>

                                <data-table
                                    id="station_streamers"
                                    ref="$datatable"
                                    :fields="fields"
                                    :api-url="listUrl"
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
                                                @click="doShowBroadcasts(row.item.links.broadcasts)"
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
                        <tab :label="$gettext('Schedule View')">
                            <div class="card-body-flush">
                                <schedule
                                    ref="$schedule"
                                    :timezone="timezone"
                                    :schedule-url="scheduleUrl"
                                    @click="doCalendarClick"
                                />
                            </div>
                        </tab>
                    </tabs>
                </div>
            </card-page>
        </div>
        <div class="col-md-4">
            <connection-info :connection-info="connectionInfo" />
        </div>

        <edit-modal
            ref="$editModal"
            :create-url="listUrl"
            :station-time-zone="timezone"
            :new-art-url="newArtUrl"
            @relist="relist"
        />
        <broadcasts-modal ref="$broadcastsModal" />
    </div>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Streamers/EditModal';
import BroadcastsModal from './Streamers/BroadcastsModal';
import Schedule from '~/components/Common/ScheduleView';
import Icon from '~/components/Common/Icon';
import ConnectionInfo from "./Streamers/ConnectionInfo";
import AlbumArt from "~/components/Common/AlbumArt";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {useAzuraCastStation} from "~/vendor/azuracast";
import {getStationApiUrl} from "~/router";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    connectionInfo: {
        type: Object,
        required: true
    }
});

const listUrl = getStationApiUrl('/streamers');
const newArtUrl = getStationApiUrl('/streamers/art');
const scheduleUrl = getStationApiUrl('/streamers/schedule');

const {timezone} = useAzuraCastStation();

const {$gettext} = useTranslate();

const fields = [
    {key: 'art', label: $gettext('Art'), sortable: false, class: 'shrink pe-0'},
    {key: 'display_name', label: $gettext('Display Name'), sortable: true},
    {key: 'streamer_username', isRowHeader: true, label: $gettext('Username'), sortable: true},
    {key: 'comments', label: $gettext('Notes'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $datatable = ref(); // Template Ref
const {relist} = useHasDatatable($datatable);

const $editModal = ref(); // Template Ref
const {doCreate, doEdit} = useHasEditModal($editModal);

const doCalendarClick = (event) => {
    doEdit(event.extendedProps.edit_url);
};

const $broadcastsModal = ref(); // Template Ref

const doShowBroadcasts = (url) => {
    $broadcastsModal.value.open(url);
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Streamer?'),
    relist
);
</script>

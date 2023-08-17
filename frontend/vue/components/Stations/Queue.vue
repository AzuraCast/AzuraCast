<template>
    <card-page :title="$gettext('Upcoming Song Queue')">
        <template #actions>
            <button
                type="button"
                class="btn btn-danger"
                @click="doClear()"
            >
                <icon icon="remove" />
                <span>
                    {{ $gettext('Clear Upcoming Song Queue') }}
                </span>
            </button>
        </template>

        <data-table
            id="station_queue"
            ref="$datatable"
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(actions)="row">
                <div class="btn-group btn-group-sm">
                    <button
                        v-if="row.item.log"
                        type="button"
                        class="btn btn-primary"
                        @click.prevent="doShowLogs(row.item.log)"
                    >
                        {{ $gettext('Logs') }}
                    </button>
                    <button
                        v-if="!row.item.sent_to_autodj"
                        type="button"
                        class="btn btn-danger"
                        @click.prevent="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
            <template #cell(song_title)="row">
                <div v-if="row.item.autodj_custom_uri">
                    {{ row.item.autodj_custom_uri }}
                </div>
                <div v-else-if="row.item.song.title">
                    <b>{{ row.item.song.title }}</b><br>
                    {{ row.item.song.artist }}
                </div>
                <div v-else>
                    {{ row.item.song.text }}
                </div>
            </template>
            <template #cell(played_at)="row">
                {{ formatTime(row.item.played_at) }}<br>
                <small>{{ formatRelativeTime(row.item.played_at) }}</small>
            </template>
            <template #cell(source)="row">
                <div v-if="row.item.is_request">
                    {{ $gettext('Listener Request') }}
                </div>
                <div v-else-if="row.item.playlist">
                    {{ $gettext('Playlist') }}: {{ row.item.playlist }}
                </div>
            </template>
        </data-table>
    </card-page>

    <queue-logs-modal ref="$logsModal" />
</template>

<script setup>
import DataTable from '../Common/DataTable';
import QueueLogsModal from './Queue/LogsModal';
import Icon from "~/components/Common/Icon";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import useHasDatatable from "~/functions/useHasDatatable";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";
import CardPage from "~/components/Common/CardPage.vue";
import {useLuxon} from "~/vendor/luxon";
import {getStationApiUrl} from "~/router";

const listUrl = getStationApiUrl('/queue');
const clearUrl = getStationApiUrl('/queue/clear');

const {$gettext} = useTranslate();

const fields = [
    {key: 'actions', label: $gettext('Actions'), sortable: false},
    {key: 'song_title', isRowHeader: true, label: $gettext('Song Title'), sortable: false},
    {key: 'played_at', label: $gettext('Expected to Play at'), sortable: false},
    {key: 'source', label: $gettext('Source'), sortable: false}
];

const {timezone} = useAzuraCastStation();

const {DateTime} = useLuxon();

const getDateTime = (timestamp) =>
    DateTime.fromSeconds(timestamp).setZone(timezone);

const {timeConfig} = useAzuraCast();

const formatTime = (time) => getDateTime(time).toLocaleString(
    {...DateTime.TIME_WITH_SECONDS, ...timeConfig}
);

const formatRelativeTime = (time) => getDateTime(time).toRelative();

const $datatable = ref(); // Template Ref
const {relist} = useHasDatatable($datatable);

const $logsModal = ref(); // Template Ref
const doShowLogs = (logs) => {
    $logsModal.value?.show(logs);
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Queue Item?'),
    relist
);

const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doClear = () => {
    confirmDelete({
        title: $gettext('Clear Upcoming Song Queue?'),
        confirmButtonText: $gettext('Clear'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.post(clearUrl.value)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
}
</script>

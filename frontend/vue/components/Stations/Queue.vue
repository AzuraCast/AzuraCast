<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Upcoming Song Queue') }}
            </h2>
        </b-card-header>
        <div class="card-actions">
            <b-button
                variant="outline-danger"
                @click="doClear()"
            >
                <icon icon="remove" />
                {{ $gettext('Clear Upcoming Song Queue') }}
            </b-button>
        </div>
        <data-table
            id="station_queue"
            ref="$datatable"
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(actions)="row">
                <b-button-group>
                    <b-button
                        v-if="row.item.log"
                        size="sm"
                        variant="primary"
                        @click.prevent="doShowLogs(row.item.log)"
                    >
                        {{ $gettext('Logs') }}
                    </b-button>
                    <b-button
                        v-if="!row.item.sent_to_autodj"
                        size="sm"
                        variant="danger"
                        @click.prevent="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </b-button>
                </b-button-group>
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
    </b-card>

    <queue-logs-modal ref="$logsModal" />
</template>

<script setup>
import DataTable from '../Common/DataTable';
import QueueLogsModal from './Queue/LogsModal';
import Icon from "~/components/Common/Icon";
import {DateTime} from 'luxon';
import {useAzuraCast} from "~/vendor/azuracast";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import useHasDatatable from "~/functions/useHasDatatable";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    clearUrl: {
        type: String,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'actions', label: $gettext('Actions'), sortable: false},
    {key: 'song_title', isRowHeader: true, label: $gettext('Song Title'), sortable: false},
    {key: 'played_at', label: $gettext('Expected to Play at'), sortable: false},
    {key: 'source', label: $gettext('Source'), sortable: false}
];

const getDateTime = (timestamp) =>
    DateTime.fromSeconds(timestamp).setZone(props.stationTimeZone);

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

const {wrapWithLoading, confirmDelete, notifySuccess} = useNotify();
const {axios} = useAxios();

const doClear = () => {
    confirmDelete({
        title: $gettext('Clear Upcoming Song Queue?'),
        confirmButtonText: $gettext('Clear'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.post(props.clearUrl)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
}
</script>

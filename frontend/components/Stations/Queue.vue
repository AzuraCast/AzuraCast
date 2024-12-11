<template>
    <card-page :title="$gettext('Upcoming Song Queue')">
        <template #actions>
            <button
                type="button"
                class="btn btn-danger"
                @click="doClear()"
            >
                <icon :icon="IconRemove" />
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
            :hide-on-loading="false"
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
                {{ formatTimestampAsTime(row.item.played_at) }}<br>
                <small>{{ formatTimestampAsRelative(row.item.played_at) }}</small>
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

<script setup lang="ts">
import DataTable, {DataTableField} from '../Common/DataTable.vue';
import QueueLogsModal from './Queue/LogsModal.vue';
import Icon from "~/components/Common/Icon.vue";
import {useTranslate} from "~/vendor/gettext";
import {computed, ref} from "vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import CardPage from "~/components/Common/CardPage.vue";
import {getStationApiUrl} from "~/router";
import {IconRemove} from "~/components/Common/icons";
import {useIntervalFn} from "@vueuse/core";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {useDialog} from "~/functions/useDialog.ts";

const listUrl = getStationApiUrl('/queue');
const clearUrl = getStationApiUrl('/queue/clear');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'actions', label: $gettext('Actions'), sortable: false},
    {key: 'song_title', isRowHeader: true, label: $gettext('Song Title'), sortable: false},
    {key: 'played_at', label: $gettext('Expected to Play at'), sortable: false},
    {key: 'source', label: $gettext('Source'), sortable: false}
];

const {
    formatTimestampAsTime,
    formatTimestampAsRelative
} = useStationDateTimeFormatter();

const $datatable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($datatable);

useIntervalFn(
    relist,
    computed(() => (document.hidden) ? 60000 : 30000)
);

const $logsModal = ref<InstanceType<typeof QueueLogsModal> | null>(null);
const doShowLogs = (logs) => {
    $logsModal.value?.show(logs);
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Queue Item?'),
    relist
);

const {confirmDelete} = useDialog();
const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doClear = () => {
    confirmDelete({
        title: $gettext('Clear Upcoming Song Queue?'),
        confirmButtonText: $gettext('Clear'),
    }).then((result) => {
        if (result.value) {
            axios.post(clearUrl.value).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
}
</script>

<template>
    <h2 class="outside-card-header mb-1">
        {{ $gettext('System Debugger') }}
    </h2>

    <div class="row row-of-cards">
        <div class="col-md-6">
            <card-page
                header-id="hdr_clear_cache"
                :title="$gettext('Clear Cache')"
            >
                <div class="card-body">
                    <p class="card-text">
                        {{ $gettext('Clearing the application cache may log you out of your session.') }}
                    </p>
                </div>

                <template #footer_actions>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="makeDebugCall(clearCacheUrl)"
                    >
                        {{ $gettext('Clear Cache') }}
                    </button>
                </template>
            </card-page>
        </div>
        <div class="col-md-6">
            <card-page
                header-id="hdr_clear_queues"
                :title="$gettext('Clear All Message Queues')"
            >
                <div class="card-body">
                    <p class="card-text">
                        {{ $gettext('This will clear any pending unprocessed messages in all message queues.') }}
                    </p>
                </div>

                <template #footer_actions>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="makeDebugCall(clearQueuesUrl)"
                    >
                        {{ $gettext('Clear All Message Queues') }}
                    </button>
                </template>
            </card-page>
        </div>
    </div>

    <card-page
        class="mb-3"
        header-id="hdr_sync_tasks"
        :title="$gettext('Synchronization Tasks')"
    >
        <data-table
            ref="$datatable"
            :fields="syncTaskFields"
            :items="syncTasks"
            :show-toolbar="false"
        >
            <template #cell(name)="row">
                <h5>{{ row.item.task }}</h5>
                {{ row.item.pattern }}
            </template>
            <template #cell(actions)="row">
                <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    @click="makeDebugCall(row.item.url)"
                >
                    {{ $gettext('Run Task') }}
                </button>
            </template>
        </data-table>
    </card-page>

    <card-page
        class="mb-3"
        header-id="hdr_message_queues"
        :title="$gettext('Message Queues')"
    >
        <div class="card-body">
            <div class="row">
                <div
                    v-for="row in queueTotals"
                    :key="row.name"
                    class="col"
                >
                    <h5 class="mb-0">
                        {{ row.name }}
                    </h5>

                    <p>
                        {{
                            $gettext(
                                '%{messages} queued messages',
                                {messages: row.count}
                            )
                        }}
                    </p>

                    <div class="buttons">
                        <button
                            type="button"
                            class="btn btn-sm btn-primary"
                            @click="makeDebugCall(row.url)"
                        >
                            {{ $gettext('Clear Queue') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </card-page>

    <card-page
        header-id="hdr_station_debugging"
        :title="$gettext('Station-Specific Debugging')"
    >
        <div class="card-body">
            <tabs>
                <tab
                    v-for="station in stations"
                    :key="station.id"
                    :label="station.name"
                >
                    <h3>{{ station.name }}</h3>

                    <div class="row">
                        <div class="col-md-4">
                            <h5>{{ $gettext('AutoDJ Queue') }}</h5>

                            <div class="buttons">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    @click="makeDebugCall(station.clearQueueUrl)"
                                >
                                    {{ $gettext('Clear Queue') }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    @click="makeDebugCall(station.getNextSongUrl)"
                                >
                                    {{ $gettext('Get Next Song') }}
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5>{{ $gettext('Get Now Playing') }}</h5>

                            <div class="buttons">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    @click="makeDebugCall(station.getNowPlayingUrl)"
                                >
                                    {{ $gettext('Run Task') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </tab>
            </tabs>
        </div>
    </card-page>

    <task-output-modal ref="$modal" />
</template>

<script setup>
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import DataTable from "~/components/Common/DataTable.vue";
import {useTranslate} from "~/vendor/gettext";
import CardPage from "~/components/Common/CardPage.vue";
import {useLuxon} from "~/vendor/luxon";
import TaskOutputModal from "~/components/Admin/Debug/TaskOutputModal.vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    clearCacheUrl: {
        type: String,
        required: true
    },
    clearQueuesUrl: {
        type: String,
        required: true
    },
    syncTasks: {
        type: Array,
        required: true
    },
    queueTotals: {
        type: Array,
        required: true
    },
    stations: {
        type: Array,
        required: true
    }
});

const {$gettext} = useTranslate();
const {timestampToRelative} = useLuxon();

const syncTaskFields = [
    {key: 'name', isRowHeader: true, label: $gettext('Task Name'), sortable: true},
    {
        key: 'time',
        label: $gettext('Last Run'),
        formatter: (value) => (value === 0)
            ? $gettext('Not Run')
            : timestampToRelative(value),
        sortable: true
    },
    {
        key: 'nextRun',
        label: $gettext('Next Run'),
        formatter: (value) => timestampToRelative(value),
        sortable: true
    },
    {key: 'actions', label: $gettext('Actions')}
];

const $datatable = ref(); // Template Ref
useHasDatatable($datatable);

const $modal = ref();

const {axios} = useAxios();
const {notifySuccess} = useNotify();

const makeDebugCall = (url) => {
    axios.put(url).then((resp) => {
        if (resp.data.logs) {
            $modal.value.open(resp.data.logs);
        } else {
            notifySuccess(resp.data.message);
        }
    });
}
</script>

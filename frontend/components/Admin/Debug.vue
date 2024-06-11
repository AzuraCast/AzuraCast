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
    >
        <template #header="{id}">
            <div class="d-md-flex align-items-center">
                <div class="flex-fill my-0">
                    <h2
                        :id="id"
                        class="card-title"
                    >
                        {{ $gettext('Synchronization Tasks') }}
                    </h2>
                </div>
                <div class="flex-shrink buttons mt-2 mt-md-0">
                    <button
                        type="button"
                        class="btn btn-dark"
                        @click="resetSyncTasks()"
                    >
                        <icon :icon="IconRefresh" />
                        <span>{{ $gettext('Refresh') }}</span>
                    </button>
                </div>
            </div>
        </template>

        <data-table
            ref="$datatable"
            :fields="syncTaskFields"
            :items="syncTasks"
            :loading="syncTasksLoading"
            handle-client-side
            :show-toolbar="false"
        >
            <template #cell(name)="row">
                <h5>{{ row.item.task }}</h5>
                <span v-if="row.item.pattern">
                    {{ row.item.pattern }}
                </span>
                <span v-else>
                    {{ $gettext('Custom') }}
                </span>
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
    >
        <template #header="{id}">
            <div class="d-md-flex align-items-center">
                <div class="flex-fill my-0">
                    <h2
                        :id="id"
                        class="card-title"
                    >
                        {{ $gettext('Message Queues') }}
                    </h2>
                </div>
                <div class="flex-shrink buttons mt-2 mt-md-0">
                    <button
                        type="button"
                        class="btn btn-dark"
                        @click="resetQueueTotals()"
                    >
                        <icon :icon="IconRefresh" />
                        <span>{{ $gettext('Refresh') }}</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="card-body">
            <loading :loading="queueTotalsLoading">
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
            </loading>
        </div>
    </card-page>

    <card-page
        header-id="hdr_station_debugging"
        :title="$gettext('Station-Specific Debugging')"
    >
        <div class="card-body">
            <loading
                :loading="stationsLoading"
                lazy
            >
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
            </loading>
        </div>
    </card-page>

    <task-output-modal ref="$modal" />
</template>

<script setup lang="ts">
import {ref} from "vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {useTranslate} from "~/vendor/gettext";
import CardPage from "~/components/Common/CardPage.vue";
import {useLuxon} from "~/vendor/luxon";
import TaskOutputModal from "~/components/Admin/Debug/TaskOutputModal.vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import {getApiUrl} from "~/router.ts";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState.ts";
import Loading from "~/components/Common/Loading.vue";
import {IconRefresh} from "~/components/Common/icons.ts";
import Icon from "~/components/Common/Icon.vue";
import useAutoRefreshingAsyncState from "~/functions/useAutoRefreshingAsyncState.ts";

const listSyncTasksUrl = getApiUrl('/admin/debug/sync-tasks');
const listQueueTotalsUrl = getApiUrl('/admin/debug/queues');
const listStationsUrl = getApiUrl('/admin/debug/stations');
const clearCacheUrl = getApiUrl('/admin/debug/clear-cache');
const clearQueuesUrl = getApiUrl('/admin/debug/clear-queue');

const {axios} = useAxios();

const {state: syncTasks, isLoading: syncTasksLoading, execute: resetSyncTasks} = useAutoRefreshingAsyncState(
    () => axios.get(listSyncTasksUrl.value).then(r => r.data),
    [],
    {
        timeout: 60000
    }
);

const {state: queueTotals, isLoading: queueTotalsLoading, execute: resetQueueTotals} = useAutoRefreshingAsyncState(
    () => axios.get(listQueueTotalsUrl.value).then(r => r.data),
    [],
    {
        timeout: 60000
    }
);

const {state: stations, isLoading: stationsLoading} = useRefreshableAsyncState(
    () => axios.get(listStationsUrl.value).then(r => r.data),
    [],
);

const {$gettext} = useTranslate();
const {timestampToRelative} = useLuxon();

const syncTaskFields: DataTableField[] = [
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

const $datatable = ref<DataTableTemplateRef>(null);
useHasDatatable($datatable);

const $modal = ref<InstanceType<typeof TaskOutputModal> | null>(null);

const {notifySuccess} = useNotify();

const makeDebugCall = (url) => {
    axios.put(url).then((resp) => {
        if (resp.data.logs) {
            $modal.value?.open(resp.data.logs);
        } else {
            notifySuccess(resp.data.message);
        }
    });
}
</script>

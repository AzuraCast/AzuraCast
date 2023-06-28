<template>
    <h2 class="outside-card-header mb-1">
        {{ $gettext('System Debugger') }}
    </h2>

    <div class="row">
        <div class="col-md-6">
            <section
                class="card mb-3"
                aria-labelledby="hdr_clear_cache"
            >
                <div class="card-header text-bg-primary">
                    <h2
                        id="hdr_clear_cache"
                        class="card-title"
                    >
                        {{ $gettext('Clear Cache') }}
                    </h2>
                </div>
                <div class="card-body">
                    <p>
                        {{ $gettext('Clearing the application cache may log you out of your session.') }}
                    </p>

                    <div class="buttons">
                        <a
                            class="btn btn-sm btn-primary"
                            role="button"
                            :href="clearCacheUrl"
                        >
                            {{ $gettext('Clear Cache') }}
                        </a>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section
                class="card mb-3"
                aria-labelledby="hdr_clear_queues"
            >
                <div class="card-header text-bg-primary">
                    <h2
                        id="hdr_clear_queues"
                        class="card-title"
                    >
                        {{ $gettext('Clear All Message Queues') }}
                    </h2>
                </div>
                <div class="card-body">
                    <p>
                        {{ $gettext('This will clear any pending unprocessed messages in all message queues.') }}
                    </p>
                    <div class="buttons">
                        <a
                            class="btn btn-sm btn-primary"
                            role="button"
                            :href="clearQueuesUrl"
                        >
                            {{ $gettext('Clear All Message Queues') }}
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section
        class="card mb-3"
        aria-labelledby="hdr_sync_tasks"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_sync_tasks"
                class="card-title"
            >
                {{ $gettext('Synchronization Tasks') }}
            </h2>
        </div>

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
                <a
                    class="btn btn-sm btn-primary"
                    role="button"
                    :href="row.item.url"
                >
                    {{ $gettext('Run Task') }}
                </a>
            </template>
        </data-table>
    </section>

    <section
        class="card mb-3"
        aria-labelledby="hdr_message_queues"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_message_queues"
                class="card-title"
            >
                {{ $gettext('Message Queues') }}
            </h2>
        </div>
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
                        <a
                            class="btn btn-sm btn-primary"
                            role="button"
                            :href="row.url"
                        >
                            {{ $gettext('Clear Queue') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="card"
        aria-labelledby="hdr_station_debugging"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_station_debugging"
                class="card-title"
            >
                {{ $gettext('Station-Specific Debugging') }}
            </h2>
        </div>
        <b-tabs
            pills
            card
        >
            <b-tab
                v-for="station in stations"
                :key="station.id"
                :title="station.name"
            >
                <h3>{{ station.name }}</h3>

                <div class="row">
                    <div class="col-md-4">
                        <h5>{{ $gettext('AutoDJ Queue') }}</h5>

                        <div class="buttons">
                            <a
                                class="btn btn-sm btn-primary"
                                role="button"
                                :href="station.clearQueueUrl"
                            >
                                {{ $gettext('Clear Queue') }}
                            </a>
                            <a
                                class="btn btn-sm btn-primary"
                                role="button"
                                :href="station.getNextSongUrl"
                            >
                                {{ $gettext('Get Next Song') }}
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h5>{{ $gettext('Get Now Playing') }}</h5>

                        <div class="buttons">
                            <a
                                class="btn btn-sm btn-primary"
                                role="button"
                                :href="station.getNowPlayingUrl"
                            >
                                {{ $gettext('Run Task') }}
                            </a>
                        </div>
                    </div>
                </div>
            </b-tab>
        </b-tabs>
    </section>
</template>

<script setup>
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import DataTable from "~/components/Common/DataTable.vue";
import {DateTime} from "luxon";
import {useAzuraCast} from "~/vendor/azuracast";
import {useTranslate} from "~/vendor/gettext";

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
const {timeConfig} = useAzuraCast();

const syncTaskFields = [
    {key: 'name', isRowHeader: true, label: $gettext('Task Name'), sortable: true},
    {
        key: 'time',
        label: $gettext('Last Run'),
        formatter: (value) => {
            if (value === 0) {
                return $gettext('Not Run');
            }

            return DateTime.fromSeconds(value).toRelative({
                ...timeConfig
            });
        },
        sortable: true
    },
    {
        key: 'nextRun',
        label: $gettext('Next Run'),
        formatter: (value) => {
            return DateTime.fromSeconds(value).toRelative({
                ...timeConfig
            });
        },
        sortable: true
    },
    {key: 'actions', label: $gettext('Actions')}
];

const $datatable = ref(); // Template Ref
useHasDatatable($datatable);

</script>

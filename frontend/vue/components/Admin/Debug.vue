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
                    <a
                        class="btn btn-sm btn-primary"
                        role="button"
                        :href="clearCacheUrl"
                    >
                        {{ $gettext('Clear Cache') }}
                    </a>
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
                    <a
                        class="btn btn-sm btn-primary"
                        role="button"
                        :href="clearQueuesUrl"
                    >
                        {{ $gettext('Clear All Message Queues') }}
                    </a>
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
                <a
                    class="btn btn-sm btn-primary"
                    role="button"
                    :href="row.item.url"
                >
                    {{ $gettext('Run Task') }}
                </a>
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
    </card-page>

    <card-page
        header-id="hdr_station_debugging"
        :title="$gettext('Station-Specific Debugging')"
    >
        <div class="card-body">
            <o-tabs
                nav-tabs-class="nav-tabs"
                content-class="mt-3"
            >
                <o-tab-item
                    v-for="station in stations"
                    :key="station.id"
                    :label="station.name"
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
                </o-tab-item>
            </o-tabs>
        </div>
    </card-page>
</template>

<script setup>
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import DataTable from "~/components/Common/DataTable.vue";
import {DateTime} from "luxon";
import {useAzuraCast} from "~/vendor/azuracast";
import {useTranslate} from "~/vendor/gettext";
import CardPage from "~/components/Common/CardPage.vue";

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

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
            ref="datatable"
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

    <queue-logs-modal ref="logs_modal" />
</template>

<script>
import DataTable from '../Common/DataTable';
import QueueLogsModal from './Queue/LogsModal';
import Icon from "~/components/Common/Icon";
import {DateTime} from 'luxon';
import {useAzuraCast} from "~/vendor/azuracast";

/* TODO Options API */

export default {
    name: 'StationQueue',
    components: {QueueLogsModal, DataTable, Icon},
    props: {
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
    },
    data() {
        return {
            fields: [
                {key: 'actions', label: this.$gettext('Actions'), sortable: false},
                {key: 'song_title', isRowHeader: true, label: this.$gettext('Song Title'), sortable: false},
                {key: 'played_at', label: this.$gettext('Expected to Play at'), sortable: false},
                {key: 'source', label: this.$gettext('Source'), sortable: false}
            ]
        };
    },
    methods: {
        formatTime(time) {
            const {timeConfig} = useAzuraCast();

            return this.getDateTime(time).toLocaleString(
                {...DateTime.TIME_WITH_SECONDS, ...timeConfig}
            );
        },
        formatRelativeTime(time) {
            return this.getDateTime(time).toRelative();
        },
        getDateTime(timestamp) {
            return DateTime.fromSeconds(timestamp).setZone(this.stationTimeZone);
        },
        doShowLogs(logs) {
            this.$refs.logs_modal.show(logs);
        },
        doDelete(url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Queue Item?'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(url)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.$refs.datatable.refresh();
                    });
                }
            });
        },
        doClear() {
            this.$confirmDelete({
                title: this.$gettext('Clear Upcoming Song Queue?'),
                confirmButtonText: this.$gettext('Clear'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.post(this.clearUrl)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.$refs.datatable.refresh();
                    });
                }
            });
        }
    }
};
</script>

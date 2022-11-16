<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_queue" v-translate>Upcoming Song Queue</h2>
            </b-card-header>
            <div class="card-actions">
                <b-button variant="outline-danger" @click="doClear()">
                    <icon icon="remove"></icon>
                    <translate key="lang_btn_clear_requests">Clear Upcoming Song Queue</translate>
                </b-button>
            </div>
            <data-table ref="datatable" id="station_queue" :fields="fields" :api-url="listUrl">
                <template #cell(actions)="row">
                    <b-button-group>
                        <b-button v-if="row.item.log" size="sm" variant="primary"
                                  @click.prevent="doShowLogs(row.item.log)">
                            <translate key="lang_btn_logs">Logs</translate>
                        </b-button>
                        <b-button v-if="!row.item.sent_to_autodj" size="sm" variant="danger"
                                  @click.prevent="doDelete(row.item.links.self)">
                            <translate key="lang_btn_delete">Delete</translate>
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
                        <translate key="lang_source_request">Listener Request</translate>
                    </div>
                    <div v-else-if="row.item.playlist">
                        <translate key="lang_source_playlist">Playlist: </translate>
                        {{ row.item.playlist }}
                    </div>
                </template>
            </data-table>
        </b-card>

        <queue-logs-modal ref="logs_modal"></queue-logs-modal>
    </div>
</template>

<script>
import DataTable from '../Common/DataTable';
import QueueLogsModal from './Queue/LogsModal';
import Icon from "~/components/Common/Icon";
import {DateTime} from 'luxon';

export default {
    name: 'StationQueue',
    components: {QueueLogsModal, DataTable, Icon},
    props: {
        listUrl: String,
        clearUrl: String,
        stationTimeZone: String
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
            return this.getDateTime(time).toLocaleString(
                {...DateTime.TIME_WITH_SECONDS, ...App.time_config}
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

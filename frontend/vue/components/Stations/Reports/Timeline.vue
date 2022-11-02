<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    <translate key="lang_title">Song Playback Timeline</translate>
                </h2>
                <div class="flex-shrink">
                    <a class="btn btn-bg" id="btn-export" :href="exportUrl" target="_blank">
                        <icon icon="file_download"></icon>
                        <translate key="lang_download_csv_button">Download CSV</translate>
                    </a>

                    <date-range-dropdown time-picker v-model="dateRange" :tz="stationTimeZone"
                                         @update="relist"></date-range-dropdown>
                </div>
            </div>
        </div>
        <data-table ref="datatable" responsive paginated select-fields
                    :fields="fields" :apiUrl="apiUrl">
            <template #cell(datetime)="row">
                {{ formatTimestamp(row.item.played_at) }}
            </template>
            <template #cell(datetime_station)="row">
                {{ formatTimestampStation(row.item.played_at) }}
            </template>
            <template #cell(listeners_start)="row">
                {{ row.item.listeners_start }}
            </template>
            <template #cell(delta)="row">
                <template v-if="row.item.delta_total > 0">
                    <big><span class="text-success">
                        <icon icon="trending_up"></icon>
                        {{ abs(row.item.delta_total) }}
                    </span></big>
                </template>
                <template v-else-if="row.item.delta_total < 0">
                    <big><span class="text-danger">
                        <icon icon="trending_down"></icon>
                        {{ abs(row.item.delta_total) }}
                    </span></big>
                </template>
                <template v-else>
                    <big>0</big>
                </template>
            </template>
            <template #cell(song)="row">
                <div :class="{'text-muted': !row.item.is_visible}">
                    <template v-if="row.item.song.title">
                        <b>{{ row.item.song.title }}</b><br>
                        {{ row.item.song.artist }}
                    </template>
                    <template v-else>
                        {{ row.item.song.text }}
                    </template>
                </div>
            </template>
            <template #cell(source)="row">
                <template v-if="row.item.is_request">
                    <translate key="lang_source_request">Listener Request</translate>
                </template>
                <template v-else-if="row.item.playlist">
                    <translate key="lang_playlist">Playlist:</translate>
                    {{ row.item.playlist }}
                </template>
                <template v-else-if="row.item.streamer">
                    <translate key="lang_streamer">Live Streamer:</translate>
                    {{ row.item.streamer }}
                </template>
                <template v-else>
                    &nbsp;
                </template>
            </template>
        </data-table>
    </div>
</template>

<script>
import Icon from "~/components/Common/Icon";
import DataTable from "~/components/Common/DataTable";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown";
import {DateTime} from 'luxon';

export default {
    name: 'StationsReportsTimeline',
    components: {DateRangeDropdown, DataTable, Icon},
    props: {
        baseApiUrl: String,
        stationTimeZone: String
    },
    data() {
        let nowTz = DateTime.now().setZone(this.stationTimeZone);

        return {
            dateRange: {
                startDate: nowTz.minus({days: 13}).toJSDate(),
                endDate: nowTz.toJSDate(),
            },
            fields: [
                {
                    key: 'datetime',
                    label: this.$gettext('Date/Time (Browser)'),
                    selectable: true,
                    sortable: false
                },
                {
                    key: 'datetime_station',
                    label: this.$gettext('Date/Time (Station)'),
                    sortable: false,
                    selectable: true,
                    visible: false
                },
                {
                    key: 'listeners_start',
                    label: this.$gettext('Listeners'),
                    selectable: true,
                    sortable: false
                },
                {
                    key: 'delta',
                    label: this.$gettext('Change'),
                    selectable: true,
                    sortable: false
                },
                {
                    key: 'song',
                    isRowHeader: true,
                    label: this.$gettext('Song Title'),
                    selectable: true,
                    sortable: false
                },
                {
                    key: 'source',
                    label: this.$gettext('Source'),
                    selectable: true,
                    sortable: false
                }
            ],
        }
    },
    computed: {
        apiUrl() {
            let apiUrl = new URL(this.baseApiUrl, document.location);

            let apiUrlParams = apiUrl.searchParams;
            apiUrlParams.set('start', DateTime.fromJSDate(this.dateRange.startDate).toISO());
            apiUrlParams.set('end', DateTime.fromJSDate(this.dateRange.endDate).toISO());

            return apiUrl.toString();
        },
        exportUrl() {
            let exportUrl = new URL(this.apiUrl, document.location);
            let exportUrlParams = exportUrl.searchParams;

            exportUrlParams.set('format', 'csv');

            return exportUrl.toString();
        },
    },
    methods: {
        relist() {
            this.$refs.datatable.relist();
        },
        abs(val) {
            return Math.abs(val);
        },
        formatTimestamp(unix_timestamp) {
            return DateTime.fromSeconds(unix_timestamp).toLocaleString(
                {...DateTime.DATETIME_SHORT, ...App.time_config}
            );
        },
        formatTimestampStation(unix_timestamp) {
            return DateTime.fromSeconds(unix_timestamp).setZone(this.stationTimeZone).toLocaleString(
                {...DateTime.DATETIME_SHORT, ...App.time_config}
            );
        }
    }
};
</script>

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

                    <date-range-picker
                        ref="picker"
                        controlContainerClass=""
                        opens="left" show-dropdowns time-picker
                        :ranges="dateRanges"
                        v-model="dateRange" @update="relist">
                        <template #input="datePicker">
                            <a class="btn btn-bg dropdown-toggle" id="reportrange" href="#" @click.prevent="">
                                <icon icon="date_range"></icon>
                                {{ datePicker.rangeText }}
                            </a>
                        </template>
                    </date-range-picker>
                </div>
            </div>
        </div>
        <data-table ref="datatable" responsive paginated
                    :fields="fields" :apiUrl="apiUrl">
            <template #cell(datetime)="row">
                {{ formatTimestamp(row.item.played_at) }}
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
                <template v-if="row.item.song.title">
                    <b>{{ row.item.song.title }}</b><br>
                    {{ row.item.song.artist }}
                </template>
                <template v-else>
                    {{ row.item.song.text }}
                </template>
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

<style lang="css">
@import '../../../node_modules/vue2-daterange-picker/dist/vue2-daterange-picker.css';
</style>

<script>
import Icon from "../../Common/Icon";
import DateRangePicker from 'vue2-daterange-picker';
import DataTable from "../../Common/DataTable";

export default {
    name: 'StationsReportsTimeline',
    components: {DateRangePicker, DataTable, Icon},
    props: {
        baseApiUrl: String,
    },
    data() {
        return {
            dateRange: {
                startDate: moment().subtract(13, 'days').toDate(),
                endDate: moment().toDate(),
            },
            fields: [
                {key: 'datetime', label: this.$gettext('Date/Time'), sortable: false},
                {key: 'listeners_start', label: this.$gettext('Listeners'), sortable: false},
                {key: 'delta', label: this.$gettext('Change'), sortable: false},
                {key: 'song', isRowHeader: true, label: this.$gettext('Song Title'), sortable: false},
                {key: 'source', label: this.$gettext('Source'), sortable: false}
            ],
        }
    },
    computed: {
        dateRanges() {
            let ranges = {};
            ranges[this.$gettext('Today')] = [
                moment().toDate(),
                moment().toDate()
            ];
            ranges[this.$gettext('Yesterday')] = [
                moment().subtract(1, 'days').toDate(),
                moment().subtract(1, 'days').toDate()
            ];
            ranges[this.$gettext('Last 7 Days')] = [
                moment().subtract(6, 'days').toDate(),
                moment().toDate()
            ];
            ranges[this.$gettext('Last 14 Days')] = [
                moment().subtract(13, 'days').toDate(),
                moment().toDate()
            ];
            ranges[this.$gettext('Last 30 Days')] = [
                moment().subtract(29, 'days').toDate(),
                moment().toDate()
            ];
            ranges[this.$gettext('This Month')] = [
                moment().startOf('month').toDate(),
                moment().endOf('month').toDate()
            ];
            ranges[this.$gettext('Last Month')] = [
                moment().subtract(1, 'month').startOf('month').toDate(),
                moment().subtract(1, 'month').endOf('month').toDate()
            ];

            return ranges;
        },
        apiUrl() {
            let params = {};
            params.start = moment(this.dateRange.startDate).format('YYYY-MM-DD');
            params.end = moment(this.dateRange.endDate).format('YYYY-MM-DD');

            return this.baseApiUrl + '?start=' + params.start + '&end=' + params.end;
        },
        exportUrl() {
            return this.apiUrl + '&format=csv';
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
            return moment.unix(unix_timestamp).format('lll');
        }
    }
};
</script>

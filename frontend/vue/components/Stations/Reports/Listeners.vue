<template>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header bg-primary-dark">
                    <div class="d-flex align-items-center">
                        <h2 class="card-title flex-fill my-0">
                            <translate key="lang_header">Listeners</translate>
                        </h2>
                        <div class="flex-shrink">
                            <a class="btn btn-bg" id="btn-export" :href="exportUrl" target="_blank">
                                <icon icon="file_download"></icon>
                                <translate key="lang_download_csv_button">Download CSV</translate>
                            </a>

                            <date-range-dropdown time-picker :min-date="minDate" :max-date="maxDate"
                                                 :tz="stationTimeZone" :ranges="dateRanges"
                                                 v-model="dateRange" @update="updateListeners">
                                <template #input="datePicker">
                                    <a class="btn btn-bg dropdown-toggle" id="reportrange" href="#" @click.prevent="">
                                        <icon icon="date_range"></icon>
                                        <template v-if="isLive">
                                            <translate key="lang_live_listeners">Live Listeners</translate>
                                        </template>
                                        <template v-else>
                                            {{ datePicker.rangeText }}
                                        </template>
                                    </a>
                                </template>
                            </date-range-dropdown>
                        </div>
                    </div>
                </div>
                <div id="map">
                    <StationReportsListenersMap :listeners="listeners"></StationReportsListenersMap>
                </div>
                <div>
                    <div class="card-body row">
                        <div class="col-md-4">
                            <h5>
                                <translate key="lang_unique_listeners">Unique Listeners</translate>
                                <br>
                                <small>
                                    <translate key="lang_for_selected_period">for selected period</translate>
                                </small>
                            </h5>
                            <h3>{{ listeners.length }}</h3>
                        </div>
                        <div class="col-md-4">
                            <h5>
                                <translate key="lang_tlh">Total Listener Hours</translate>
                                <br>
                                <small>
                                    <translate key="lang_for_selected_period">for selected period</translate>
                                </small>
                            </h5>
                            <h3>{{ totalListenerHours }}</h3>
                        </div>
                    </div>

                    <data-table ref="datatable" id="station_playlists" paginated handle-client-side
                                :fields="fields" :responsive="false" :items="listeners">
                        <template #cell(ip)="row">
                            {{ row.item.ip }}
                        </template>
                        <template #cell(time)="row">
                            {{ formatTime(row.item.connected_time) }}
                        </template>
                        <template #cell(time_sec)="row">
                            {{ row.item.connected_time }}
                        </template>
                        <template #cell(user_agent)="row">
                            <span v-if="row.item.is_mobile">
                                <icon icon="smartphone"></icon>
                                <span class="sr-only">
                                    <translate key="lang_device_mobile">Mobile Device</translate>
                                </span>
                            </span>
                            <span v-else>
                                <icon icon="desktop_windows"></icon>
                                <span class="sr-only">
                                    <translate key="lang_device_desktop">Desktop Device</translate>
                                </span>
                            </span>
                            {{ row.item.user_agent }} <br>
                            <small>{{ row.item.client }}</small>
                        </template>
                        <template #cell(stream)="row">
                            <span v-if="row.item.mount_name == ''">
                                <translate key="lang_stream_unknown">Unknown</translate>
                            </span>
                            <span v-else>
                                {{ row.item.mount_name }}<br>
                                <small v-if="row.item.mount_is_local">
                                    <translate key="lang_mount_local">Local</translate>
                                </small>
                                <small v-else>
                                    <translate key="lang_mount_remote">Remote</translate>
                                </small>
                            </span>
                        </template>
                        <template #cell(location)="row">
                            <span v-if="row.item.location.status == 'success'">
                                {{ row.item.location.region }}, {{ row.item.location.country }}
                            </span>
                            <span v-else-if="row.item.location.message">
                                {{ row.item.location.message }}
                            </span>
                            <span v-else>
                                <translate key="lang_location_unknown">Unknown</translate>
                            </span>
                        </template>
                    </data-table>
                </div>
                <div class="card-body card-padding-sm text-muted">
                    {{ attribution }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import StationReportsListenersMap from "./Listeners/Map";
import Icon from "~/components/Common/Icon";
import formatTime from "~/functions/formatTime";
import DataTable from "~/components/Common/DataTable";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown";
import {DateTime} from 'luxon';

export default {
    name: 'StationReportsListeners',
    components: {DateRangeDropdown, DataTable, StationReportsListenersMap, Icon},
    props: {
        apiUrl: String,
        attribution: String,
        stationTimeZone: String,
    },
    data() {
        let liveTime = DateTime.now().setZone(this.stationTimeZone).plus({days: 1}).toJSDate();

        return {
            listeners: [],
            liveTime: liveTime,
            dateRange: {
                startDate: liveTime,
                endDate: liveTime
            },
            fields: [
                { key: 'ip', label: this.$gettext('IP'), sortable: false },
                { key: 'time', label: this.$gettext('Time'), sortable: false },
                { key: 'time_sec', label: this.$gettext('Time (sec)'), sortable: false },
                { key: 'user_agent', isRowHeader: true, label: this.$gettext('User Agent'), sortable: false },
                { key: 'stream', label: this.$gettext('Stream'), sortable: false },
                { key: 'location', label: this.$gettext('Location'), sortable: false }
            ]
        };
    },
    computed: {
        nowTz() {
            return DateTime.now().setZone(this.stationTimeZone);
        },
        minDate() {
            return this.nowTz.minus({years: 5}).toJSDate();
        },
        maxDate() {
            return this.nowTz.plus({days: 5}).toJSDate();
        },
        dateRanges() {
            let ranges = {};
            ranges[this.$gettext('Live Listeners')] = [
                this.liveTime,
                this.liveTime
            ];
            ranges[this.$gettext('Today')] = [
                this.nowTz.startOf('day').toJSDate(),
                this.nowTz.endOf('day').toJSDate()
            ];
            ranges[this.$gettext('Yesterday')] = [
                this.nowTz.minus({days: 1}).startOf('day').toJSDate(),
                this.nowTz.minus({days: 1}).endOf('day').toJSDate()
            ];
            ranges[this.$gettext('Last 7 Days')] = [
                this.nowTz.minus({days: 6}).startOf('day').toJSDate(),
                this.nowTz.endOf('day').toJSDate()
            ];
            ranges[this.$gettext('Last 30 Days')] = [
                this.nowTz.minus({days: 29}).startOf('day').toJSDate(),
                this.nowTz.endOf('day').toJSDate()
            ];
            ranges[this.$gettext('This Month')] = [
                this.nowTz.startOf('month').startOf('day').toJSDate(),
                this.nowTz.endOf('month').endOf('day').toJSDate()
            ];
            ranges[this.$gettext('Last Month')] = [
                this.nowTz.minus({months: 1}).startOf('month').startOf('day').toJSDate(),
                this.nowTz.minus({months: 1}).endOf('month').endOf('day').toJSDate()
            ];
            return ranges;
        },
        isLive() {
            return DateTime.fromJSDate(this.liveTime).equals(DateTime.fromJSDate(this.dateRange.startDate));
        },
        exportUrl() {
            let params = {};
            let export_url = this.apiUrl + '?format=csv';

            if (!this.isLive) {
                params.start = DateTime.fromJSDate(this.dateRange.startDate).toISO();
                params.end = DateTime.fromJSDate(this.dateRange.endDate).toISO();
                export_url += '&start=' + params.start + '&end=' + params.end;
            }

            return export_url;
        },
        totalListenerHours() {
            let tlh_seconds = 0;
            this.listeners.forEach(function (listener) {
                tlh_seconds += listener.connected_time;
            });

            let tlh_hours = tlh_seconds / 3600;
            return Math.round((tlh_hours + 0.00001) * 100) / 100;
        }
    },
    mounted() {
        this.updateListeners();
    },
    methods: {
        formatTime(time) {
            return formatTime(time);
        },
        updateListeners() {
            let params = {};
            if (!this.isLive) {
                params.start = DateTime.fromJSDate(this.dateRange.startDate).toISO();
                params.end = DateTime.fromJSDate(this.dateRange.endDate).toISO();
            }

            this.$wrapWithLoading(
                this.axios.get(this.apiUrl, {params: params})
            ).then((resp) => {
                this.listeners = resp.data;

                if (this.isLive) {
                    setTimeout(this.updateListeners, (!document.hidden) ? 15000 : 30000);
                }
            }).catch(() => {
                if (this.isLive && (!error.response || error.response.data.code !== 403)) {
                    setTimeout(this.updateListeners, (!document.hidden) ? 30000 : 120000);
                }
            });
        }
    }
}
</script>

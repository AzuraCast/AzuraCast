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
                                                 :ranges="dateRanges" v-model="dateRange" @update="updateListeners">
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
import handleAxiosError from "~/functions/handleAxiosError";
import Icon from "~/components/Common/Icon";
import formatTime from "~/functions/formatTime";
import DataTable from "~/components/Common/DataTable";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown";

export default {
    name: 'StationReportsListeners',
    components: {DateRangeDropdown, DataTable, StationReportsListenersMap, Icon},
    props: {
        apiUrl: String,
        attribution: String
    },
    data() {
        let liveTime = moment().add(1, 'days').toDate();

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
        minDate() {
            return moment().subtract(5, 'years').toDate();
        },
        maxDate() {
            return moment().add(5, 'days').toDate();
        },
        dateRanges() {
            let ranges = {};
            ranges[this.$gettext('Live Listeners')] = [
                this.liveTime,
                this.liveTime
            ];
            ranges[this.$gettext('Today')] = [
                moment().startOf('day').toDate(),
                moment().endOf('day').toDate()
            ];
            ranges[this.$gettext('Yesterday')] = [
                moment().subtract(1, 'days').startOf('day').toDate(),
                moment().subtract(1, 'days').endOf('day').toDate()
            ];
            ranges[this.$gettext('Last 7 Days')] = [
                moment().subtract(6, 'days').startOf('day').toDate(),
                moment().endOf('day').toDate()
            ];
            ranges[this.$gettext('Last 30 Days')] = [
                moment().subtract(29, 'days').startOf('day').toDate(),
                moment().endOf('day').toDate()
            ];
            ranges[this.$gettext('This Month')] = [
                moment().startOf('month').startOf('day').toDate(),
                moment().endOf('month').endOf('day').toDate()
            ];
            ranges[this.$gettext('Last Month')] = [
                moment().subtract(1, 'month').startOf('month').startOf('day').toDate(),
                moment().subtract(1, 'month').endOf('month').endOf('day').toDate()
            ];
            return ranges;
        },
        isLive() {
            return moment(this.liveTime).isSame(moment(this.dateRange.startDate));
        },
        exportUrl() {
            let params = {};
            let export_url = this.apiUrl + '?format=csv';

            if (!this.isLive) {
                params.start = moment(this.dateRange.startDate).format('YYYY-MM-DD H:mm:ss');
                params.end = moment(this.dateRange.endDate).format('YYYY-MM-DD H:mm:ss');
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
                params.start = moment(this.dateRange.startDate).format('YYYY-MM-DD H:mm:ss');
                params.end = moment(this.dateRange.endDate).format('YYYY-MM-DD H:mm:ss');
            }

            this.axios.get(this.apiUrl, {params: params}).then((resp) => {
                this.listeners = resp.data;

                if (this.isLive) {
                    setTimeout(this.updateListeners, 15000);
                }
            }).catch((err) => {
                handleAxiosError(err);

                if (this.isLive) {
                    setTimeout(this.updateListeners, 30000);
                }
            });
        }
    }
}
</script>

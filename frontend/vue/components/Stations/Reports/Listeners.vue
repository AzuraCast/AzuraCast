<template>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header bg-primary-dark">
                    <div class="d-flex align-items-center">
                        <div class="flex-fill my-0">
                            <h2 class="card-title">
                                {{ $gettext('Listeners') }}
                            </h2>
                        </div>
                        <div class="flex-shrink buttons">
                            <a
                                id="btn-export"
                                class="btn btn-bg"
                                :href="exportUrl"
                                target="_blank"
                            >
                                <icon icon="file_download" />
                                {{ $gettext('Download CSV') }}
                            </a>

                            <date-range-dropdown
                                v-if="!isLive"
                                v-model="dateRange"
                                time-picker
                                :min-date="minDate"
                                :max-date="maxDate"
                                :tz="stationTimeZone"
                                @update="updateListeners"
                            />
                        </div>
                    </div>
                </div>
                <b-tabs
                    pills
                    card
                >
                    <b-tab
                        key="live"
                        active
                        :title="$gettext('Live Listeners')"
                        no-body
                        @click="setIsLive(true)"
                    />
                    <b-tab
                        key="not-live"
                        :title="$gettext('Listener History')"
                        no-body
                        @click="setIsLive(false)"
                    />
                </b-tabs>
                <div id="map">
                    <StationReportsListenersMap :listeners="listeners" />
                </div>
                <div>
                    <div class="card-body row">
                        <div class="col-md-4">
                            <h5>
                                {{ $gettext('Unique Listeners') }}
                                <br>
                                <small>
                                    {{ $gettext('for selected period') }}
                                </small>
                            </h5>
                            <h3>{{ listeners.length }}</h3>
                        </div>
                        <div class="col-md-4">
                            <h5>
                                {{ $gettext('Total Listener Hours') }}
                                <br>
                                <small>
                                    {{ $gettext('for selected period') }}
                                </small>
                            </h5>
                            <h3>{{ totalListenerHours }}</h3>
                        </div>
                    </div>

                    <data-table
                        id="station_playlists"
                        ref="datatable"
                        paginated
                        handle-client-side
                        :fields="fields"
                        :responsive="false"
                        :items="listeners"
                    >
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
                            <div>
                                <span v-if="row.item.is_mobile">
                                    <icon icon="smartphone" />
                                    <span class="sr-only">
                                        {{ $gettext('Mobile Device') }}
                                    </span>
                                </span>
                                <span v-else>
                                    <icon icon="desktop_windows" />
                                    <span class="sr-only">
                                        {{ $gettext('Desktop Device') }}
                                    </span>
                                </span>

                                {{ row.item.user_agent }}
                            </div>
                            <div v-if="row.item.device.client">
                                <small>{{ row.item.device.client }}</small>
                            </div>
                        </template>
                        <template #cell(stream)="row">
                            <span v-if="row.item.mount_name === ''">
                                {{ $gettext('Unknown') }}
                            </span>
                            <span v-else>
                                {{ row.item.mount_name }}<br>
                                <small v-if="row.item.mount_is_local">
                                    {{ $gettext('Local') }}
                                </small>
                                <small v-else>
                                    {{ $gettext('Remote') }}
                                </small>
                            </span>
                        </template>
                        <template #cell(location)="row">
                            <span v-if="row.item.location.description">
                                {{ row.item.location.description }}
                            </span>
                            <span v-else>
                                {{ $gettext('Unknown') }}
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
        apiUrl: {
            type: String,
            required: true
        },
        attribution: {
            type: String,
            required: true
        },
        stationTimeZone: {
            type: String,
            required: true
        },
    },
    data() {
        const nowTz = DateTime.now().setZone(this.stationTimeZone);

        return {
            isLive: true,
            listeners: [],
            dateRange: {
                startDate: nowTz.minus({days: 1}).toJSDate(),
                endDate: nowTz.toJSDate()
            },
            fields: [
                {key: 'ip', label: this.$gettext('IP'), sortable: false},
                {key: 'time', label: this.$gettext('Time'), sortable: false},
                {key: 'time_sec', label: this.$gettext('Time (sec)'), sortable: false},
                {key: 'user_agent', isRowHeader: true, label: this.$gettext('User Agent'), sortable: false},
                {key: 'stream', label: this.$gettext('Stream'), sortable: false},
                {key: 'location', label: this.$gettext('Location'), sortable: false}
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
        exportUrl() {
            let exportUrl = new URL(this.apiUrl, document.location);
            let exportUrlParams = exportUrl.searchParams;
            exportUrlParams.set('format', 'csv');

            if (!this.isLive) {
                exportUrlParams.set('start', DateTime.fromJSDate(this.dateRange.startDate).toISO());
                exportUrlParams.set('end', DateTime.fromJSDate(this.dateRange.endDate).toISO());
            }

            return exportUrl.toString();
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
        setIsLive(newValue) {
            this.isLive = newValue;
            this.updateListeners();
        },
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
            }).catch((error) => {
                if (this.isLive && (!error.response || error.response.data.code !== 403)) {
                    setTimeout(this.updateListeners, (!document.hidden) ? 30000 : 120000);
                }
            });
        }
    }
}
</script>

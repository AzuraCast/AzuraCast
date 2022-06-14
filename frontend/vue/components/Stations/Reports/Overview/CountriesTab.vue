<template>
    <b-tab :title="langTitle">
        <b-overlay variant="card" :show="loading">
            <div class="card-body py-5" v-if="loading">
                &nbsp;
            </div>
            <div v-else>
                <div class="card-body">
                    <b-row>
                        <b-col md="6" class="mb-4">
                            <fieldset>
                                <legend>
                                    <translate key="hdr_top_by_listeners">Top Countries by Listeners</translate>
                                </legend>

                                <pie-chart style="width: 100%;" :data="top_listeners.datasets"
                                           :labels="top_listeners.labels">
                                    <span v-html="top_listeners.alt"></span>
                                </pie-chart>
                            </fieldset>
                        </b-col>
                        <b-col md="6" class="mb-4">
                            <fieldset>
                                <legend>
                                    <translate
                                        key="hdr_top_by_connected_seconds">Top Countries by Connected Time</translate>
                                </legend>

                                <pie-chart style="width: 100%;" :data="top_connected_time.datasets"
                                           :labels="top_connected_time.labels">
                                    <span v-html="top_connected_time.alt"></span>
                                </pie-chart>
                            </fieldset>
                        </b-col>
                    </b-row>
                </div>

                <data-table ref="datatable" id="browsers_table" paginated handle-client-side
                            :fields="fields" :responsive="false" :items="all">
                    <template #cell(connected_seconds_calc)="row">
                        {{ formatTime(row.item.connected_seconds) }}
                    </template>
                </data-table>
            </div>
        </b-overlay>
    </b-tab>
</template>

<script>
import {DateTime} from "luxon";
import PieChart from "~/components/Common/PieChart";
import formatTime from "~/functions/formatTime";
import DataTable from "~/components/Common/DataTable";

export default {
    name: 'CountriesTab',
    components: {DataTable, PieChart},
    props: {
        dateRange: Object,
        apiUrl: String,
    },
    data() {
        return {
            loading: true,
            all: [],
            top_listeners: {
                labels: [],
                datasets: [],
                alt: ''
            },
            top_connected_time: {
                labels: [],
                datasets: [],
                alt: ''
            },
            fields: [
                {key: 'country', label: this.$gettext('Country'), sortable: true},
                {key: 'listeners', label: this.$gettext('Listeners'), sortable: true},
                {key: 'connected_seconds_calc', label: this.$gettext('Time'), sortable: false},
                {key: 'connected_seconds', label: this.$gettext('Time (sec)'), sortable: true}
            ]
        };
    },
    watch: {
        dateRange() {
            this.relist();
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Countries');
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.loading = true;
            this.axios.get(this.apiUrl, {
                params: {
                    start: DateTime.fromJSDate(this.dateRange.startDate).toISO(),
                    end: DateTime.fromJSDate(this.dateRange.endDate).toISO()
                }
            }).then((response) => {
                this.all = response.data.all;
                this.top_listeners = response.data.top_listeners;
                this.top_connected_time = response.data.top_connected_time;

                this.loading = false;
            });
        },
        formatTime(time) {
            return formatTime(time);
        }
    }
}
</script>

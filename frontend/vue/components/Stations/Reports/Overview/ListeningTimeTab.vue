<template>
    <b-overlay variant="card" :show="loading">
        <div class="card-body py-5" v-if="loading">
            &nbsp;
        </div>
        <div v-else>
            <div class="card-body">
                <fieldset>
                    <legend>
                        <translate key="chart_listening_time">Listeners by Listening Time</translate>
                    </legend>

                    <pie-chart style="width: 100%;" :data="chart.datasets"
                               :labels="chart.labels" :aspect-ratio="4">
                        <span v-html="chart.alt"></span>
                    </pie-chart>
                </fieldset>
            </div>

            <data-table ref="datatable" id="listening_time_table" paginated handle-client-side
                        :fields="fields" :responsive="false" :items="all">
            </data-table>
        </div>
    </b-overlay>
</template>

<script>
import {DateTime} from "luxon";
import PieChart from "~/components/Common/PieChart";
import DataTable from "~/components/Common/DataTable";
import IsMounted from "~/components/Common/IsMounted";

export default {
    name: 'ListeningTimeTab',
    components: {DataTable, PieChart},
    mixins: [IsMounted],
    props: {
        dateRange: Object,
        apiUrl: String
    },
    data() {
        return {
            loading: true,
            all: [],
            chart: {
                labels: [],
                datasets: [],
                alt: ''
            },
            fields: [
                {key: 'label', label: this.$gettext('Listening Time'), sortable: false},
                {key: 'value', label: this.$gettext('Listeners'), sortable: false}
            ]
        };
    },
    watch: {
        dateRange() {
            if (this.isMounted) {
                this.relist();
            }
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
                this.chart = response.data.chart;

                this.loading = false;
            });
        }
    }
}
</script>

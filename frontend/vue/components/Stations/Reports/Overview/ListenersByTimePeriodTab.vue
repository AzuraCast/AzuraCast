<template>
    <b-overlay variant="card" :show="loading">
        <div class="card-body py-5" v-if="loading">
            &nbsp;
        </div>
        <div class="card-body" v-else>
            <b-row>
                <b-col md="12" class="mb-4">
                    <fieldset>
                        <legend>
                            <translate key="hdr_listeners_by_day">Listeners by Day</translate>
                        </legend>

                        <time-series-chart style="width: 100%;" :data="chartData.daily.metrics">
                            <span v-html="chartData.daily.alt"></span>
                        </time-series-chart>
                    </fieldset>
                </b-col>
                <b-col md="6" class="mb-4">
                    <fieldset>
                        <legend>
                            <translate key="hdr_listeners_by_dow">Listeners by Day of Week</translate>
                        </legend>

                        <pie-chart style="width: 100%;" :data="chartData.day_of_week.metrics"
                                   :labels="chartData.day_of_week.labels">
                            <span v-html="chartData.day_of_week.alt"></span>
                        </pie-chart>
                    </fieldset>
                </b-col>
                <b-col md="6" class="mb-4">
                    <fieldset>
                        <legend>
                            <translate key="hdr_listeners_by_hour">Listeners by Hour</translate>
                        </legend>

                        <hour-chart style="width: 100%;" :data="chartData.hourly.metrics"
                                    :labels="chartData.hourly.labels">
                            <span v-html="chartData.hourly.alt"></span>
                        </hour-chart>
                    </fieldset>
                </b-col>
            </b-row>
        </div>
    </b-overlay>
</template>

<script>
import TimeSeriesChart from "~/components/Common/TimeSeriesChart";
import HourChart from "~/components/Stations/Reports/Overview/HourChart";
import {DateTime} from "luxon";
import PieChart from "~/components/Common/PieChart";
import IsMounted from "~/components/Common/IsMounted";

export default {
    name: 'ListenersByTimePeriodTab',
    components: {PieChart, HourChart, TimeSeriesChart},
    mixins: [IsMounted],
    props: {
        dateRange: Object,
        apiUrl: String,
    },
    data() {
        return {
            loading: true,
            chartData: {
                daily: {},
                day_of_week: {
                    labels: [],
                    metrics: [],
                    alt: ''
                },
                hourly: {
                    labels: [],
                    metrics: [],
                    alt: ''
                }
            },
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
                this.chartData = response.data;
                this.loading = false;
            });
        }
    }
}
</script>

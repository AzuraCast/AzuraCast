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
                            {{ $gettext('Listeners by Day') }}
                        </legend>

                        <time-series-chart style="width: 100%;" :data="chartData.daily.metrics">
                            <span v-html="chartData.daily.alt"></span>
                        </time-series-chart>
                    </fieldset>
                </b-col>
                <b-col md="6" class="mb-4">
                    <fieldset>
                        <legend>
                            {{ $gettext('Listeners by Day of Week') }}
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
                            {{ $gettext('Listeners by Hour') }}
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

<script setup>
import TimeSeriesChart from "~/components/Common/TimeSeriesChart";
import HourChart from "~/components/Stations/Reports/Overview/HourChart";
import {DateTime} from "luxon";
import PieChart from "~/components/Common/PieChart";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import {get, set, useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    dateRange: Object,
    apiUrl: String,
});

const loading = ref(true);

const chartData = shallowRef({
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
});

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();

const relist = () => {
    set(loading, true);
    axios.get(props.apiUrl, {
        params: {
            start: DateTime.fromJSDate(get(dateRange).startDate).toISO(),
            end: DateTime.fromJSDate(get(dateRange).endDate).toISO()
        }
    }).then((response) => {
        set(chartData, response.data);
        set(loading, false);
    });
}

const isMounted = useMounted();

watch(dateRange, () => {
    if (get(isMounted)) {
        relist();
    }
});

onMounted(() => {
    relist();
});
</script>

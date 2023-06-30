<template>
    <b-overlay
        variant="card"
        :show="loading"
    >
        <template
            v-if="loading"
        >
            &nbsp;
        </template>
        <template v-else>
            <b-row>
                <b-col
                    md="12"
                    class="mb-4"
                >
                    <fieldset>
                        <legend>
                            {{ $gettext('Listeners by Day') }}
                        </legend>

                        <time-series-chart
                            style="width: 100%;"
                            :data="chartData.daily.metrics"
                            :alt="chartData.daily.alt"
                        />
                    </fieldset>
                </b-col>
                <b-col
                    md="6"
                    class="mb-4"
                >
                    <fieldset>
                        <legend>
                            {{ $gettext('Listeners by Day of Week') }}
                        </legend>

                        <pie-chart
                            style="width: 100%;"
                            :data="chartData.day_of_week.metrics"
                            :labels="chartData.day_of_week.labels"
                            :alt="chartData.day_of_week.alt"
                        />
                    </fieldset>
                </b-col>
                <b-col
                    md="6"
                    class="mb-4"
                >
                    <fieldset>
                        <legend>
                            {{ $gettext('Listeners by Hour') }}
                        </legend>

                        <hour-chart
                            style="width: 100%;"
                            :data="chartData.hourly.metrics"
                            :labels="chartData.hourly.labels"
                            :alt="chartData.hourly.alt"
                        />
                    </fieldset>
                </b-col>
            </b-row>
        </template>
    </b-overlay>
</template>

<script setup>
import TimeSeriesChart from "~/components/Common/Charts/TimeSeriesChart.vue";
import HourChart from "~/components/Common/Charts/HourChart.vue";
import {DateTime} from "luxon";
import PieChart from "~/components/Common/Charts/PieChart.vue";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import {useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    dateRange: {
        type: Object,
        required: true
    },
    apiUrl: {
        type: String,
        required: true
    },
});

const loading = ref(true);

const chartData = shallowRef({
    daily: {
        labels: [],
        metrics: [],
        alt: []
    },
    day_of_week: {
        labels: [],
        metrics: [],
        alt: []
    },
    hourly: {
        labels: [],
        metrics: [],
        alt: []
    }
});

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();

const relist = () => {
    loading.value = true;

    axios.get(props.apiUrl, {
        params: {
            start: DateTime.fromJSDate(dateRange.value.startDate).toISO(),
            end: DateTime.fromJSDate(dateRange.value.endDate).toISO()
        }
    }).then((response) => {
        chartData.value = response.data;
        loading.value = false;
    });
}

const isMounted = useMounted();

watch(dateRange, () => {
    if (isMounted.value) {
        relist();
    }
});

onMounted(() => {
    relist();
});
</script>

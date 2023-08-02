<template>
    <loading
        :loading="isLoading"
        lazy
    >
        <div class="row">
            <div class="col-md-12 mb-4">
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
            </div>
            <div class="col-md-6 mb-4">
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
            </div>
            <div class="col-md-6 mb-4">
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
            </div>
        </div>
    </loading>
</template>

<script setup>
import TimeSeriesChart from "~/components/Common/Charts/TimeSeriesChart.vue";
import HourChart from "~/components/Common/Charts/HourChart.vue";
import PieChart from "~/components/Common/Charts/PieChart.vue";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import {useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import {useLuxon} from "~/vendor/luxon";

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

const isLoading = ref(true);

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

const {DateTime} = useLuxon();

const relist = () => {
    isLoading.value = true;

    axios.get(props.apiUrl, {
        params: {
            start: DateTime.fromJSDate(dateRange.value.startDate).toISO(),
            end: DateTime.fromJSDate(dateRange.value.endDate).toISO()
        }
    }).then((response) => {
        chartData.value = response.data;
        isLoading.value = false;
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

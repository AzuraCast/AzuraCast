<template>
    <div class="buttons mb-3">
        <input
            id="result-type-average"
            v-model="resultType"
            type="radio"
            class="btn-check"
            autocomplete="off"
            value="average"
        >
        <label
            class="btn btn-sm btn-outline-secondary"
            for="result-type-average"
        >
            {{ $gettext('Average Listeners') }}
        </label>

        <input
            id="result-type-unique"
            v-model="resultType"
            type="radio"
            class="btn-check"
            autocomplete="off"
            value="unique"
        >
        <label
            class="btn btn-sm btn-outline-secondary"
            for="result-type-unique"
        >
            {{ $gettext('Unique Listeners') }}
        </label>
    </div>

    <loading
        :loading="isLoading"
        lazy
    >
        <div class="row">
            <div class="col-md-6 mb-4">
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
            <div class="col-md-12 mb-4">
                <fieldset>
                    <legend>
                        {{ $gettext('Listeners by Hour') }}
                    </legend>

                    <div class="buttons mb-3">
                        <template
                            v-for="(hourLabel, hour) in currentHourOptions"
                            :key="hour"
                        >
                            <input
                                :id="`charts-hour-${hour}`"
                                v-model="currentHour"
                                type="radio"
                                class="btn-check"
                                autocomplete="off"
                                :value="hour"
                            >
                            <label
                                class="btn btn-sm btn-outline-secondary"
                                :for="`charts-hour-${hour}`"
                            >
                                {{ hourLabel }}
                            </label>
                        </template>
                    </div>

                    <hour-chart
                        style="width: 100%;"
                        :data="currentHourlyChart.metrics"
                        :labels="currentHourlyChart.labels"
                        :alt="currentHourlyChart.alt"
                        :aspect-ratio="3"
                    />
                </fieldset>
            </div>
        </div>
    </loading>
</template>

<script setup lang="ts">
import TimeSeriesChart from "~/components/Common/Charts/TimeSeriesChart.vue";
import HourChart from "~/components/Common/Charts/HourChart.vue";
import PieChart from "~/components/Common/Charts/PieChart.vue";
import {computed, ref, toRef, watch} from "vue";
import {useAsyncState, useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import {useLuxon} from "~/vendor/luxon";
import {useTranslate} from "~/vendor/gettext.ts";

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

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();

const {DateTime} = useLuxon();

const resultType = ref('average');

const blankChartSection = {
    labels: [],
    metrics: [],
    alt: []
};

const {state: chartData, isLoading, execute: relist} = useAsyncState(
    () => axios.get(props.apiUrl, {
        params: {
            type: resultType.value,
            start: DateTime.fromJSDate(dateRange.value.startDate).toISO(),
            end: DateTime.fromJSDate(dateRange.value.endDate).toISO()
        }
    }).then(r => r.data),
    {
        daily: {...blankChartSection},
        day_of_week: {...blankChartSection},
        hourly: {
            all: {...blankChartSection},
            day0: {...blankChartSection},
            day1: {...blankChartSection},
            day2: {...blankChartSection},
            day3: {...blankChartSection},
            day4: {...blankChartSection},
            day5: {...blankChartSection},
            day6: {...blankChartSection},
        }
    },
    {
        shallow: true
    }
);

const currentHour = ref('all');

const {$gettext} = useTranslate();

const currentHourOptions = computed(() => ({
    'all': $gettext('All Days'),
    'day0': $gettext('Monday'),
    'day1': $gettext('Tuesday'),
    'day2': $gettext('Wednesday'),
    'day3': $gettext('Thursday'),
    'day4': $gettext('Friday'),
    'day5': $gettext('Saturday'),
    'day6': $gettext('Sunday'),
}));

const currentHourlyChart = computed(() => {
    return chartData.value?.hourly[currentHour.value];
});

const isMounted = useMounted();

watch(dateRange, () => {
    if (isMounted.value) {
        relist();
    }
});

watch(resultType, () => {
    if (isMounted.value) {
        relist();
    }
});
</script>

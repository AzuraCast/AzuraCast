<template>
    <loading
        :loading="chartsLoading"
    >
        <o-tabs
            nav-tabs-class="nav-tabs"
            content-class="mt-3"
        >
            <o-tab-item
                :label="$gettext('Average Listeners')"
                active
            >
                <time-series-chart
                    style="width: 100%;"
                    :data="chartsData.average.metrics"
                    :alt="chartsData.average.alt"
                />
            </o-tab-item>
            <o-tab-item :label="$gettext('Unique Listeners')">
                <time-series-chart
                    style="width: 100%;"
                    :data="chartsData.unique.metrics"
                    :alt="chartsData.unique.alt"
                />
            </o-tab-item>
        </o-tabs>
    </loading>
</template>

<script setup>
import TimeSeriesChart from '~/components/Common/Charts/TimeSeriesChart.vue';
import {useAsyncState} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";

const props = defineProps({
    chartsUrl: {
        type: String,
        required: true
    }
});

const {axios} = useAxios();

const {state: chartsData, isLoading: chartsLoading} = useAsyncState(
    () => axios.get(props.chartsUrl).then((r) => r.data),
    {
        average: {
            metrics: [],
            alt: []
        },
        unique: {
            metrics: [],
            alt: []
        }
    }
);
</script>

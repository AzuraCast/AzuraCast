<template>
    <loading
        :loading="chartsLoading"
    >
        <tabs>
            <tab :label="$gettext('Average Listeners')">
                <time-series-chart
                    style="width: 100%;"
                    :data="chartsData.average.metrics"
                    :alt="chartsData.average.alt"
                    :aspect-ratio="3"
                />
            </tab>
            <tab :label="$gettext('Unique Listeners')">
                <time-series-chart
                    style="width: 100%;"
                    :data="chartsData.unique.metrics"
                    :alt="chartsData.unique.alt"
                    :aspect-ratio="3"
                />
            </tab>
        </tabs>
    </loading>
</template>

<script setup lang="ts">
import TimeSeriesChart from '~/components/Common/Charts/TimeSeriesChart.vue';
import {useAsyncState} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";

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

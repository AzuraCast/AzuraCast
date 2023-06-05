<template>
    <b-overlay
        variant="card"
        :show="chartsLoading"
    >
        <div
            v-if="chartsLoading"
            class="card-body py-5"
        >
            &nbsp;
        </div>
        <b-tabs
            v-else
            pills
            card
            lazy
        >
            <b-tab active>
                <template #title>
                    {{ $gettext('Average Listeners') }}
                </template>

                <time-series-chart
                    style="width: 100%;"
                    :data="chartsData.average.metrics"
                    :alt="chartsData.average.alt"
                />
            </b-tab>
            <b-tab>
                <template #title>
                    {{ $gettext('Unique Listeners') }}
                </template>

                <time-series-chart
                    style="width: 100%;"
                    :data="chartsData.unique.metrics"
                    :alt="chartsData.unique.alt"
                />
            </b-tab>
        </b-tabs>
    </b-overlay>
</template>

<script setup>
import TimeSeriesChart from '~/components/Common/Charts/TimeSeriesChart.vue';
import {useAsyncState} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

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
            alt: ''
        },
        unique: {
            metrics: [],
            alt: ''
        }
    }
);
</script>

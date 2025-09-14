<template>
    <loading
        :loading="chartsLoading"
        lazy
    >
        <tabs v-if="chartsData">
            <tab :label="$gettext('Average Listeners')">
                <time-series-chart
                    style="width: 100%;"
                    :data="chartsData.average.metrics"
                    :alt="chartsData.average.alt"
                    :aspect-ratio="3"
                    :options="dashboardChartOptions"
                />
            </tab>
            <tab :label="$gettext('Unique Listeners')">
                <time-series-chart
                    style="width: 100%;"
                    :data="chartsData.unique.metrics"
                    :alt="chartsData.unique.alt"
                    :aspect-ratio="3"
                    :options="dashboardChartOptions"
                />
            </tab>
        </tabs>
    </loading>
</template>

<script setup lang="ts">
import TimeSeriesChart from "~/components/Common/Charts/TimeSeriesChart.vue";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys} from "~/entities/Queries.ts";
import {useLuxon} from "~/vendor/luxon.ts";

const props = defineProps<{
    chartsUrl: string,
}>();

const {DateTime} = useLuxon();

const dashboardChartOptions = {
    options: {
        scales: {
            x: {
                min: Number(DateTime.utc().minus({days: 30}).toMillis()),
            }
        }
    }
};

const {axios} = useAxios();

type ChartData = {
    average: {
        metrics: any[],
        alt: any[]
    },
    unique: {
        metrics: any[],
        alt: any[]
    }
}

const {data: chartsData, isLoading: chartsLoading} = useQuery<ChartData>({
    queryKey: [QueryKeys.Dashboard, 'charts'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get(props.chartsUrl, {signal});
        return data;
    },
    placeholderData: () => ({
        average: {
            metrics: [],
            alt: []
        },
        unique: {
            metrics: [],
            alt: []
        }
    }),
    staleTime: 60 * 60 * 1000
});
</script>

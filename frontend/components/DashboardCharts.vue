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
import TimeSeriesChart from "~/components/Common/Charts/TimeSeriesChart.vue";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys} from "~/entities/Queries.ts";

const props = defineProps<{
    chartsUrl: string,
}>();

const {axios} = useAxios();

const {data: chartsData, isLoading: chartsLoading} = useQuery({
    queryKey: [QueryKeys.Dashboard, 'charts'],
    queryFn: async () => {
        const {data} = await axios.get(props.chartsUrl);
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
    })
});
</script>

<template>
    <loading :loading="isLoading" lazy>
        <fieldset>
            <legend>
                {{ $gettext('Listeners by Listening Time') }}
            </legend>

            <pie-chart
                style="width: 100%;"
                v-if="stats"
                :data="stats.chart.datasets"
                :labels="stats.chart.labels"
                :alt="stats.chart.alt"
                :aspect-ratio="4"
            />
        </fieldset>

        <data-table
            id="listening_time_table"
            ref="$dataTable"
            paginated
            :fields="fields"
            :provider="metricsItemProvider"
        />
    </loading>
</template>

<script setup lang="ts">
import PieChart from "~/components/Common/Charts/PieChart.vue";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {computed, toRef, useTemplateRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import {useLuxon} from "~/vendor/luxon";
import useHasDatatable from "~/functions/useHasDatatable";
import {DateRange} from "~/components/Stations/Reports/Overview/CommonMetricsView.vue";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useClientItemProvider} from "~/functions/dataTable/useClientItemProvider.ts";

const props = defineProps<{
    dateRange: DateRange,
    apiUrl: string,
}>();

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'label', label: $gettext('Listening Time'), sortable: false},
    {key: 'value', label: $gettext('Listeners'), sortable: false}
];

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();
const {DateTime} = useLuxon();

type ChartData = {
    all: any[],
    chart: {
        labels: any[],
        datasets: any[],
        alt: any[]
    }
}

const {data: stats, isLoading, refetch} = useQuery<ChartData>({
    queryKey: queryKeyWithStation([
        QueryKeys.StationReports,
        'listening_time_metrics',
        dateRange
    ]),
    queryFn: async ({signal}) => {
        const {data} = await axios.get(props.apiUrl, {
            signal,
            params: {
                start: DateTime.fromJSDate(dateRange.value.startDate).toISO(),
                end: DateTime.fromJSDate(dateRange.value.endDate).toISO()
            }
        });
        return data;
    },
    placeholderData: () => ({
        all: [],
        chart: {
            labels: [],
            datasets: [],
            alt: []
        }
    })
});

const metricsItemProvider = useClientItemProvider(
    computed(() => stats.value?.all ?? []),
    isLoading,
    undefined,
    async (): Promise<void> => {
        await refetch();
    }
);

const $dataTable = useTemplateRef('$dataTable');
const {navigate} = useHasDatatable($dataTable);

const isMounted = useMounted();

watch(dateRange, () => {
    if (isMounted.value) {
        navigate();
    }
});
</script>

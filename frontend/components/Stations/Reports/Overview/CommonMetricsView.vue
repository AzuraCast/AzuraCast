<template>
    <loading
        :loading="isLoading"
        lazy
    >
        <div class="row">
            <div class="col-md-6 mb-4">
                <fieldset>
                    <legend>
                        <slot name="by_listeners_legend" />
                    </legend>

                    <pie-chart
                        style="width: 100%;"
                        v-if="stats"
                        :data="stats.top_listeners.datasets"
                        :labels="stats.top_listeners.labels"
                        :alt="stats.top_listeners.alt"
                    />
                </fieldset>
            </div>
            <div class="col-md-6 mb-4">
                <fieldset>
                    <legend>
                        <slot name="by_connected_time_legend" />
                    </legend>

                    <pie-chart
                        style="width: 100%;"
                        v-if="stats"
                        :data="stats.top_connected_time.datasets"
                        :labels="stats.top_connected_time.labels"
                        :alt="stats.top_connected_time.alt"
                    />
                </fieldset>
            </div>
        </div>

        <data-table
            :id="fieldKey+'_table'"
            ref="$dataTable"
            paginated
            :fields="fields"
            :provider="metricsItemProvider"
        >
            <template #cell(connected_seconds_calc)="row">
                {{ formatTime(row.item.connected_seconds) }}
            </template>
        </data-table>
    </loading>
</template>

<script setup lang="ts">
import PieChart from "~/components/Common/Charts/PieChart.vue";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import formatTime from "~/functions/formatTime";
import {computed, toRef, useTemplateRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import {useLuxon} from "~/vendor/luxon";
import useHasDatatable from "~/functions/useHasDatatable";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useClientItemProvider} from "~/functions/dataTable/useClientItemProvider.ts";

export interface DateRange {
    startDate: Date,
    endDate: Date,
}

const props = defineProps<{
    dateRange: DateRange,
    apiUrl: string,
    fieldKey: string,
    fieldLabel: string,
}>();

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: props.fieldKey, label: props.fieldLabel, sortable: true},
    {key: 'listeners', label: $gettext('Listeners'), sortable: true},
    {key: 'connected_seconds_calc', label: $gettext('Time'), sortable: false},
    {key: 'connected_seconds', label: $gettext('Time (sec)'), sortable: true}
];

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();
const {DateTime} = useLuxon();

type ChartData = {
    all: any[],
    top_listeners: {
        labels: any[],
        datasets: any[],
        alt: any[]
    },
    top_connected_time: {
        labels: any[],
        datasets: any[],
        alt: any[]
    },
}

const metricsQuery = useQuery<ChartData>({
    queryKey: queryKeyWithStation([
        QueryKeys.StationReports,
        'common_metrics',
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
        top_listeners: {
            labels: [],
            datasets: [],
            alt: []
        },
        top_connected_time: {
            labels: [],
            datasets: [],
            alt: []
        },
    })
});

const {data: stats, isLoading, refetch} = metricsQuery;

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

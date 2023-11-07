<template>
    <loading :loading="isLoading">
        <fieldset>
            <legend>
                {{ $gettext('Listeners by Listening Time') }}
            </legend>

            <pie-chart
                style="width: 100%;"
                :data="stats.chart.datasets"
                :labels="stats.chart.labels"
                :alt="stats.chart.alt"
                :aspect-ratio="4"
            />
        </fieldset>

        <data-table
            id="listening_time_table"
            ref="$datatable"
            paginated
            handle-client-side
            :fields="fields"
            :items="stats.all"
            @refresh-clicked="reloadData()"
        />
    </loading>
</template>

<script setup lang="ts">
import PieChart from "~/components/Common/Charts/PieChart.vue";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {ref, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAsyncState, useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import {useLuxon} from "~/vendor/luxon";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";

const props = defineProps({
    dateRange: {
        type: Object,
        required: true
    },
    apiUrl: {
        type: String,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'label', label: $gettext('Listening Time'), sortable: false},
    {key: 'value', label: $gettext('Listeners'), sortable: false}
];

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();
const {DateTime} = useLuxon();

const {state: stats, isLoading, execute: reloadData} = useAsyncState(
    () => axios.get(props.apiUrl, {
        params: {
            start: DateTime.fromJSDate(dateRange.value.startDate).toISO(),
            end: DateTime.fromJSDate(dateRange.value.endDate).toISO()
        }
    }).then(r => r.data),
    {
        all: [],
        chart: {
            labels: [],
            datasets: [],
            alt: []
        }
    }
);

const $datatable = ref<DataTableTemplateRef>();
const {navigate} = useHasDatatable($datatable);

const isMounted = useMounted();

watch(dateRange, () => {
    if (isMounted.value) {
        reloadData();
        navigate();
    }
});
</script>

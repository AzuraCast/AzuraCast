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
            ref="datatable"
            paginated
            handle-client-side
            :fields="fields"
            :items="stats.all"
        />
    </loading>
</template>

<script setup>
import PieChart from "~/components/Common/Charts/PieChart.vue";
import DataTable from "~/components/Common/DataTable";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
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
    }
});

const isLoading = ref(true);
const stats = shallowRef({
    all: [],
    chart: {
        labels: [],
        datasets: [],
        alt: []
    }
});

const {$gettext} = useTranslate();

const fields = shallowRef([
    {key: 'label', label: $gettext('Listening Time'), sortable: false},
    {key: 'value', label: $gettext('Listeners'), sortable: false}
]);

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
        stats.value = {
            all: response.data.all,
            chart: response.data.chart
        };
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

<template>
    <b-overlay
        variant="card"
        :show="loading"
    >
        <div
            v-if="loading"
            class="card-body py-5"
        >
            &nbsp;
        </div>
        <div v-else>
            <div class="card-body">
                <fieldset>
                    <legend>
                        {{ $gettext('Listeners by Listening Time') }}
                    </legend>

                    <pie-chart
                        style="width: 100%;"
                        :data="stats.chart.datasets"
                        :labels="stats.chart.labels"
                        :aspect-ratio="4"
                    >
                        <span v-html="stats.chart.alt" />
                    </pie-chart>
                </fieldset>
            </div>

            <data-table
                id="listening_time_table"
                ref="datatable"
                paginated
                handle-client-side
                :fields="fields"
                :responsive="false"
                :items="stats.all"
            />
        </div>
    </b-overlay>
</template>

<script setup>
import PieChart from "~/components/Common/Charts/PieChart.vue";
import DataTable from "~/components/Common/DataTable";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {DateTime} from "luxon";
import {useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    dateRange: Object,
    apiUrl: String
});

const loading = ref(true);
const stats = shallowRef({
    all: [],
    chart: {
        labels: [],
        datasets: [],
        alt: ''
    }
});

const {$gettext} = useTranslate();

const fields = shallowRef([
    {key: 'label', label: $gettext('Listening Time'), sortable: false},
    {key: 'value', label: $gettext('Listeners'), sortable: false}
]);

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();

const relist = () => {
    loading.value = true;

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
        loading.value = false;
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

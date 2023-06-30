<template>
    <b-overlay
        variant="card"
        :show="loading"
    >
        <template
            v-if="loading"
        >
            &nbsp;
        </template>
        <template v-else>
            <div class="card-body">
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
        </template>
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
    dateRange: {
        type: Object,
        required: true
    },
    apiUrl: {
        type: String,
        required: true
    }
});

const loading = ref(true);
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

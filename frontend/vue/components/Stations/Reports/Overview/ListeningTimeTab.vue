<template>
    <b-overlay variant="card" :show="loading">
        <div class="card-body py-5" v-if="loading">
            &nbsp;
        </div>
        <div v-else>
            <div class="card-body">
                <fieldset>
                    <legend>
                        {{ $gettext('Listeners by Listening Time') }}
                    </legend>

                    <pie-chart style="width: 100%;" :data="stats.chart.datasets"
                               :labels="stats.chart.labels" :aspect-ratio="4">
                        <span v-html="stats.chart.alt"></span>
                    </pie-chart>
                </fieldset>
            </div>

            <data-table ref="datatable" id="listening_time_table" paginated handle-client-side
                        :fields="fields" :responsive="false" :items="stats.all">
            </data-table>
        </div>
    </b-overlay>
</template>

<script setup>
import PieChart from "~/components/Common/PieChart";
import DataTable from "~/components/Common/DataTable";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import gettext from "~/vendor/gettext";
import {DateTime} from "luxon";
import {get, set, useMounted} from "@vueuse/core";
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

const {$gettext} = gettext;
const fields = shallowRef([
    {key: 'label', label: $gettext('Listening Time'), sortable: false},
    {key: 'value', label: $gettext('Listeners'), sortable: false}
]);

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();

const relist = () => {
    set(loading, true);

    axios.get(props.apiUrl, {
        params: {
            start: DateTime.fromJSDate(get(dateRange).startDate).toISO(),
            end: DateTime.fromJSDate(get(dateRange).endDate).toISO()
        }
    }).then((response) => {
        set(
            stats,
            {
                all: response.data.all,
                chart: response.data.chart
            }
        );
        set(loading, false);
    });
}

const isMounted = useMounted();

watch(dateRange, () => {
    if (get(isMounted)) {
        relist();
    }
});

onMounted(() => {
    relist();
});
</script>

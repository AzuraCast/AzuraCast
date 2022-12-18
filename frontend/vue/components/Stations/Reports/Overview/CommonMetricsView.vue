<template>
    <b-overlay variant="card" :show="loading">
        <div class="card-body py-5" v-if="loading">
            &nbsp;
        </div>
        <div v-else>
            <div class="card-body">
                <b-row>
                    <b-col md="6" class="mb-4">
                        <fieldset>
                            <legend>
                                <slot name="by_listeners_legend"></slot>
                            </legend>

                            <pie-chart style="width: 100%;" :data="stats.top_listeners.datasets"
                                       :labels="stats.top_listeners.labels">
                                <span v-html="stats.top_listeners.alt"></span>
                            </pie-chart>
                        </fieldset>
                    </b-col>
                    <b-col md="6" class="mb-4">
                        <fieldset>
                            <legend>
                                <slot name="by_connected_time_legend"></slot>
                            </legend>

                            <pie-chart style="width: 100%;" :data="stats.top_connected_time.datasets"
                                       :labels="stats.top_connected_time.labels">
                                <span v-html="stats.top_connected_time.alt"></span>
                            </pie-chart>
                        </fieldset>
                    </b-col>
                </b-row>
            </div>

            <data-table ref="datatable" :id="fieldKey+'_table'" paginated handle-client-side
                        :fields="fields" :responsive="false" :items="stats.all">
                <template #cell(connected_seconds_calc)="row">
                    {{ formatTime(row.item.connected_seconds) }}
                </template>
            </data-table>
        </div>
    </b-overlay>
</template>

<script setup>
import PieChart from "~/components/Common/Charts/PieChart.vue";
import DataTable from "~/components/Common/DataTable";
import formatTime from "~/functions/formatTime";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import gettext from "~/vendor/gettext";
import {DateTime} from "luxon";
import {get, set, useMounted} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    dateRange: Object,
    apiUrl: String,
    fieldKey: String,
    fieldLabel: String,
});

const loading = ref(true);
const stats = shallowRef({
    all: [],
    top_listeners: {
        labels: [],
        datasets: [],
        alt: ''
    },
    top_connected_time: {
        labels: [],
        datasets: [],
        alt: ''
    },
});

const {$gettext} = gettext;

const fields = shallowRef([
    {key: props.fieldKey, label: props.fieldLabel, sortable: true},
    {key: 'listeners', label: $gettext('Listeners'), sortable: true},
    {key: 'connected_seconds_calc', label: $gettext('Time'), sortable: false},
    {key: 'connected_seconds', label: $gettext('Time (sec)'), sortable: true}
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
        set(stats, {
            all: response.data.all,
            top_listeners: response.data.top_listeners,
            top_connected_time: response.data.top_connected_time
        });
        set(loading, false);
    });
};

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

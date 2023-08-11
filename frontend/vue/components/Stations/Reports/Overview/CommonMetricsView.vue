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
                        :data="stats.top_connected_time.datasets"
                        :labels="stats.top_connected_time.labels"
                        :alt="stats.top_connected_time.alt"
                    />
                </fieldset>
            </div>
        </div>

        <data-table
            :id="fieldKey+'_table'"
            ref="datatable"
            paginated
            handle-client-side
            :fields="fields"
            :items="stats.all"
        >
            <template #cell(connected_seconds_calc)="row">
                {{ formatTime(row.item.connected_seconds) }}
            </template>
        </data-table>
    </loading>
</template>

<script setup>
import PieChart from "~/components/Common/Charts/PieChart.vue";
import DataTable from "~/components/Common/DataTable";
import formatTime from "~/functions/formatTime";
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
    },
    fieldKey: {
        type: String,
        required: true
    },
    fieldLabel: {
        type: String,
        required: true
    },
});

const isLoading = ref(true);
const stats = shallowRef({
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
});

const {$gettext} = useTranslate();

const fields = shallowRef([
    {key: props.fieldKey, label: props.fieldLabel, sortable: true},
    {key: 'listeners', label: $gettext('Listeners'), sortable: true},
    {key: 'connected_seconds_calc', label: $gettext('Time'), sortable: false},
    {key: 'connected_seconds', label: $gettext('Time (sec)'), sortable: true}
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
            top_listeners: response.data.top_listeners,
            top_connected_time: response.data.top_connected_time
        };

        isLoading.value = false;
    });
};

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

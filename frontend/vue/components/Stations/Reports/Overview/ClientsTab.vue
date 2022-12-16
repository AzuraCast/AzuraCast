<template>
    <common-metrics-view :date-range="dateRange" :api-url="apiUrl"
                         field-key="client" :field-label="langFieldLabel">
        <template #by_listeners_legend>
            {{ $gettext('Clients by Listeners') }}
        </template>
        <template #by_connected_time_legend>
            {{ $gettext('Clients by Connected Time') }}
        </template>
    </common-metrics-view>
</template>

<script>
import CommonMetricsView from "./CommonMetricsView";

export default {
    name: 'ClientsTab',
    components: {CommonMetricsView},
    props: {
        dateRange: Object,
        apiUrl: String,
    },
    computed: {
        langFieldLabel() {
            return this.$gettext('Client');
        }
    }
}
</script>

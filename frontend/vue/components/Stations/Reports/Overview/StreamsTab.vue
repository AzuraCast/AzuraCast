<template>
    <common-metrics-view :date-range="dateRange" :api-url="apiUrl"
                         field-key="stream" :field-label="langFieldLabel">
        <template #by_listeners_legend>
            {{ $gettext('Top Streams by Listeners') }}
        </template>
        <template #by_connected_time_legend>
            {{ $gettext('Top Streams by Connected Time') }}
        </template>
    </common-metrics-view>
</template>

<script>
import CommonMetricsView from "./CommonMetricsView";

export default {
    name: 'StreamsTab',
    components: {CommonMetricsView},
    props: {
        dateRange: Object,
        apiUrl: String,
    },
    computed: {
        langFieldLabel() {
            return this.$gettext('Stream');
        }
    }
}
</script>

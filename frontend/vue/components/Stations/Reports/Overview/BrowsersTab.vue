<template>
    <common-metrics-view :date-range="dateRange" :api-url="apiUrl"
                         field-key="browser" :field-label="langFieldLabel">
        <template #by_listeners_legend>
            {{ $gettext('Top Browsers by Listeners') }}
        </template>
        <template #by_connected_time_legend>
            {{ $gettext('Top Browsers by Connected Time') }}
        </template>
    </common-metrics-view>
</template>

<script>
import CommonMetricsView from "./CommonMetricsView";

export default {
    name: 'BrowsersTab',
    components: {CommonMetricsView},
    props: {
        dateRange: Object,
        apiUrl: String,
    },
    computed: {
        langFieldLabel() {
            return this.$gettext('Browser');
        }
    }
}
</script>

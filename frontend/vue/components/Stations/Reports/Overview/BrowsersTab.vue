<template>
    <common-metrics-view :date-range="dateRange" :api-url="apiUrl"
                         field-key="browser" :field-label="langFieldLabel">
        <template #by_listeners_legend>
            <translate key="hdr_top_by_listeners">Top Browsers by Listeners</translate>
        </template>
        <template #by_connected_time_legend>
            <translate key="hdr_top_by_connected_seconds">Top Browsers by Connected Time</translate>
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

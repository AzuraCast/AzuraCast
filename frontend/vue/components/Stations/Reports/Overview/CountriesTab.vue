<template>
    <common-metrics-view :date-range="dateRange" :api-url="apiUrl"
                         field-key="country" :field-label="langFieldLabel">
        <template #by_listeners_legend>
            <translate key="hdr_top_by_listeners">Top Countries by Listeners</translate>
        </template>
        <template #by_connected_time_legend>
            <translate key="hdr_top_by_connected_seconds">Top Countries by Connected Time</translate>
        </template>
    </common-metrics-view>
</template>

<script>
import CommonMetricsView from "./CommonMetricsView";

export default {
    name: 'CountriesTab',
    components: {CommonMetricsView},
    props: {
        dateRange: Object,
        apiUrl: String,
    },
    computed: {
        langFieldLabel() {
            return this.$gettext('Country');
        }
    }
}
</script>

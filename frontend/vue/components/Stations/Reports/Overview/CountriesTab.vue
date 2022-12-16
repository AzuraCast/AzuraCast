<template>
    <common-metrics-view :date-range="dateRange" :api-url="apiUrl"
                         field-key="country" :field-label="langFieldLabel">
        <template #by_listeners_legend>
            {{ $gettext('Top Countries by Listeners') }}
        </template>
        <template #by_connected_time_legend>
            {{ $gettext('Top Countries by Connected Time') }}
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

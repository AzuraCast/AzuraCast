<template>
    <common-metrics-view
        :date-range="dateRange"
        :api-url="apiUrl"
        field-key="country"
        :field-label="$gettext('Country')"
    >
        <template #by_listeners_legend>
            {{ $gettext('Top Countries by Listeners') }}
        </template>
        <template #by_connected_time_legend>
            {{ $gettext('Top Countries by Connected Time') }}
        </template>
    </common-metrics-view>
</template>

<script setup>
import CommonMetricsView from "./CommonMetricsView";

const props = defineProps({
    dateRange: Object,
    apiUrl: String,
});
</script>

<template>
    <common-metrics-view
        :date-range="dateRange"
        :api-url="apiUrl"
        field-key="browser"
        :field-label="$gettext('Browser')"
    >
        <template #by_listeners_legend>
            {{ $gettext('Top Browsers by Listeners') }}
        </template>
        <template #by_connected_time_legend>
            {{ $gettext('Top Browsers by Connected Time') }}
        </template>
    </common-metrics-view>
</template>

<script setup>
import CommonMetricsView from "./CommonMetricsView";

const props = defineProps({
    dateRange: {
        type: Object,
        required: true
    },
    apiUrl: {
        type: String,
        required: true
    },
});
</script>

<template>
    <common-metrics-view
        :date-range="dateRange"
        :api-url="apiUrl"
        field-key="stream"
        :field-label="$gettext('Stream')"
    >
        <template #by_listeners_legend>
            {{ $gettext('Top Streams by Listeners') }}
        </template>
        <template #by_connected_time_legend>
            {{ $gettext('Top Streams by Connected Time') }}
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

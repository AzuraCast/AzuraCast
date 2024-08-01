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

<script setup lang="ts">
import CommonMetricsView from "./CommonMetricsView.vue";

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

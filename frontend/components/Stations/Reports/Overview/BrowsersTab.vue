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

<script setup lang="ts">
import CommonMetricsView, {DateRange} from "~/components/Stations/Reports/Overview/CommonMetricsView.vue";

defineProps<{
    dateRange: DateRange,
    apiUrl: string,
}>();
</script>

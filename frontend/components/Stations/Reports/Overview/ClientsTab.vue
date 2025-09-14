<template>
    <common-metrics-view
        :date-range="dateRange"
        :api-url="apiUrl"
        field-key="client"
        :field-label="$gettext('Client')"
    >
        <template #by_listeners_legend>
            {{ $gettext('Clients by Listeners') }}
        </template>
        <template #by_connected_time_legend>
            {{ $gettext('Clients by Connected Time') }}
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

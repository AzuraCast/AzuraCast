<template>
    <full-height-card>
        <template #title>
            <template v-if="stationName">
                {{ stationName }}
            </template>
            <template v-else>
                {{ $gettext('Schedule') }}
            </template>
        </template>
        
        <template #default>
            <div id="station-schedule-calendar">
                <schedule-view
                    :options="{
                        timeZone: stationTimeZone,
                        events: scheduleUrl
                    }"
                />
            </div>
        </template>
    </full-height-card>
</template>

<script setup lang="ts">
import ScheduleView from "~/components/Common/ScheduleView.vue";
import FullHeightCard from "~/components/Public/FullHeightCard.vue";

defineProps<{
    scheduleUrl: string,
    stationName: string,
    stationTimeZone: string
}>();
</script>

<style lang="scss" scoped>
#station-schedule-calendar {
    overflow-y: auto;
}
</style>

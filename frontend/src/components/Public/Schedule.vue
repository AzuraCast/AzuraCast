<template>
    <section
        id="content"
        class="full-height-wrapper"
        role="main"
    >
        <div class="container">
            <div class="card">
                <div class="card-header text-bg-primary">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink">
                            <h2 class="card-title py-2">
                                <template v-if="stationName">
                                    {{ stationName }}
                                </template>
                                <template v-else>
                                    {{ $gettext('Schedule') }}
                                </template>
                            </h2>
                        </div>
                    </div>
                </div>

                <div id="station-schedule-calendar">
                    <schedule
                        ref="schedule"
                        :timezone="stationTimeZone"
                        :schedule-url="scheduleUrl"
                        :station-time-zone="stationTimeZone"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import Schedule from '~/components/Common/ScheduleView.vue';

const props = defineProps({
    scheduleUrl: {
        type: String,
        required: true
    },
    stationName: {
        type: String,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    }
});
</script>

<style lang="scss" scoped>
#station-schedule-calendar {
    overflow-y: auto;
}
</style>

<template>
    <section
        id="content"
        role="main"
        class="d-flex align-items-stretch"
        style="height: 100vh;"
    >
        <div
            class="container pt-5 pb-5 h-100"
            style="flex: 1;"
        >
            <div
                class="card"
                style="height: 100%;"
            >
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

<script setup>
import Schedule from '~/components/Common/ScheduleView';

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

<style lang="scss">
.schedule.embed {
    .container {
        max-width: 100%;
        padding: 0 !important;
    }
}

#station-schedule-calendar {
    overflow-y: auto;
}
</style>

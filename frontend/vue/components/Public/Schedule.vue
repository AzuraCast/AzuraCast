<template>
    <section id="content" role="main" class="d-flex align-items-stretch" style="height: 100vh;">
        <div class="container pt-5 pb-5 h-100" style="flex: 1;">
            <div class="card" style="height: 100%;">
                <div class="card-header bg-primary-dark">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink">
                            <h2 class="card-title py-2">
                                <template v-if="stationName">
                                    {{ stationName }}
                                </template>
                                <template v-else>
                                    <translate key="lang_title">Schedule</translate>
                                </template>
                            </h2>
                        </div>
                    </div>
                </div>

                <div id="station-schedule-calendar">
                    <schedule ref="schedule" :schedule-url="scheduleUrl"
                              :station-time-zone="stationTimeZone"></schedule>
                </div>
            </div>
        </div>
    </section>
</template>

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

<script>
import Schedule from '~/components/Common/ScheduleView';

export default {
    components: { Schedule },
    props: {
        scheduleUrl: String,
        stationName: String,
        stationTimeZone: String
    },
};
</script>

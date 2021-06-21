<template>
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
            <schedule ref="schedule" :schedule-url="scheduleUrl" :station-time-zone="stationTimeZone"
                                :locale="locale"></schedule>
        </div>
    </div>
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
import Schedule from '../Common/ScheduleView';

export default {
    components: { Schedule },
    props: {
        scheduleUrl: String,
        stationName: String,
        locale: String,
        stationTimeZone: String
    },
    mounted () {
        moment.relativeTimeThreshold('ss', 1);
        moment.relativeTimeRounding(function (value) {
            return Math.round(value * 10) / 10;
        });
    },
    methods: {
        formatTime (time) {
            return moment(time).tz(this.stationTimeZone).format('LT');
        }
    }
};
</script>

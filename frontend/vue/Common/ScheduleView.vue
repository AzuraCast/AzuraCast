<template>
    <full-calendar ref="calendar" :plugins="plugins" :locales="locales" :time-zone="stationTimeZone"
                   theme-system="bootstrap4"
                   :now-indicator="true" default-view="timeGridWeek" default-timed-event-duration="00:20"
                   :locale="locale" :header="false" :footer="false" height="auto"
                   :events="scheduleUrl" @eventClick="onEventClick">
    </full-calendar>
</template>

<script>
import FullCalendar from '@fullcalendar/vue';
import allLocales from '@fullcalendar/core/locales-all';
import momentPlugin from '@fullcalendar/moment';
import momentTimezonePlugin from '@fullcalendar/moment-timezone';
import timeGridPlugin from '@fullcalendar/timegrid';

export default {
    name: 'Schedule',
    components: { FullCalendar },
    props: {
        scheduleUrl: String,
        stationTimeZone: String,
        locale: String
    },
    data () {
        return {
            locales: allLocales,
            plugins: [momentPlugin, momentTimezonePlugin, timeGridPlugin]
        };
    },
    methods: {
        refresh () {

        },
        onEventClick (arg) {
            this.$emit('click', arg.event);
        }
    }
};
</script>

<style lang="scss">
@import '~@fullcalendar/core/main.css';
@import '~@fullcalendar/daygrid/main.css';
@import '~@fullcalendar/timegrid/main.css';
</style>

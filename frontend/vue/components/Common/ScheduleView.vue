<template>
    <full-calendar ref="calendar" :options="calendarOptions" @eventClick="onEventClick"></full-calendar>
</template>

<script>
import '@fullcalendar/core/vdom';
import FullCalendar from '@fullcalendar/vue';
import allLocales from '@fullcalendar/core/locales-all';
import luxonPlugin from '@fullcalendar/luxon';
import timeGridPlugin from '@fullcalendar/timegrid';

export default {
    name: 'Schedule',
    components: {FullCalendar},
    props: {
        scheduleUrl: String,
        stationTimeZone: String
    },
    data() {
        return {
            calendarOptions: {
                locale: App.locale_short,
                locales: allLocales,
                plugins: [luxonPlugin, timeGridPlugin],
                initialView: 'timeGridWeek',
                timeZone: this.stationTimeZone,
                themeSystem: 'bootstrap',
                nowIndicator: true,
                defaultTimedEventDuration: '00:20',
                headerToolbar: false,
                footerToolbar: false,
                height: 'auto',
                events: this.scheduleUrl,
                eventClick: this.onEventClick
            }
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

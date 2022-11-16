<template>
    <full-calendar ref="calendar" :options="calendarOptions"></full-calendar>
</template>

<script>
import '@fullcalendar/core/vdom';
import FullCalendar from '@fullcalendar/vue';
import allLocales from '@fullcalendar/core/locales-all';
import luxon2Plugin from '@fullcalendar/luxon2';
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
                plugins: [luxon2Plugin, timeGridPlugin],
                initialView: 'timeGridWeek',
                timeZone: this.stationTimeZone,
                themeSystem: 'bootstrap',
                nowIndicator: true,
                defaultTimedEventDuration: '00:20',
                headerToolbar: false,
                footerToolbar: false,
                height: 'auto',
                events: this.scheduleUrl,
                eventClick: this.onEventClick,
                eventDidMount: this.onEventDidMount,
                views: {
                    timeGridWeek: {
                        slotLabelFormat: {
                            ...App.time_config,
                            hour: 'numeric',
                            minute: '2-digit',
                            omitZeroMinute: true,
                            meridiem: 'short'
                        }
                    }
                }
            }
        };
    },
    methods: {
        refresh() {

        },
        onEventDidMount(info) {
            $(info.el).tooltip({
                title: info.event.extendedProps.description,
                placement: 'top',
                trigger: 'hover',
                container: 'body',
                offset: 0
            });
        },
        onEventClick(arg) {
            this.$emit('click', arg.event);
        }
    }
};
</script>

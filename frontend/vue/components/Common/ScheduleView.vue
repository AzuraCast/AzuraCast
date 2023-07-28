<template>
    <full-calendar
        ref="calendar"
        :options="calendarOptions"
    />
</template>

<script setup>
import FullCalendar from '@fullcalendar/vue3';
import allLocales from '@fullcalendar/core/locales-all';
import luxon3Plugin from '@fullcalendar/luxon3';
import timeGridPlugin from '@fullcalendar/timegrid';
import {shallowRef} from "vue";
import {useAzuraCast} from "~/vendor/azuracast";

const props = defineProps({
    timezone: {
        type: String,
        required: true
    },
    scheduleUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['click']);

const onEventClick = (arg) => {
    emit('click', arg.event);
};

const {localeShort, timeConfig} = useAzuraCast();

const calendarOptions = shallowRef({
    locale: localeShort,
    locales: allLocales,
    plugins: [luxon3Plugin, timeGridPlugin],
    initialView: 'timeGridWeek',
    timeZone: props.timezone,
    nowIndicator: true,
    defaultTimedEventDuration: '00:20',
    headerToolbar: false,
    footerToolbar: false,
    height: 'auto',
    events: props.scheduleUrl,
    eventClick: onEventClick,
    views: {
        timeGridWeek: {
            slotLabelFormat: {
                ...timeConfig,
                hour: 'numeric',
                minute: '2-digit',
                omitZeroMinute: true,
                meridiem: 'short'
            }
        }
    }
});
</script>

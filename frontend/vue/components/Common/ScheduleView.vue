<template>
    <full-calendar
        ref="calendar"
        :options="calendarOptions"
    />
</template>

<script setup>
import '@fullcalendar/core/vdom';
import FullCalendar from '@fullcalendar/vue3';
import allLocales from '@fullcalendar/core/locales-all';
import luxon2Plugin from '@fullcalendar/luxon2';
import timeGridPlugin from '@fullcalendar/timegrid';
import {shallowRef} from "vue";
import {useAzuraCast} from "~/vendor/azuracast";

const props = defineProps({
    scheduleUrl: {
        type: String,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['click']);

const onEventDidMount = (info) => {
    let desc = info?.event?.extendedProps?.description || null;
    if (desc !== null) {
        // eslint-ignore-line no-undef
        $(info.el).tooltip({
            title: desc,
            placement: 'top',
            trigger: 'hover',
            container: 'body',
            offset: 0
        });
    }
};

const onEventClick = (arg) => {
    emit('click', arg.event);
};

const {localeShort, timeConfig} = useAzuraCast();

const calendarOptions = shallowRef({
    locale: localeShort,
    locales: allLocales,
    plugins: [luxon2Plugin, timeGridPlugin],
    initialView: 'timeGridWeek',
    timeZone: props.stationTimeZone,
    themeSystem: 'bootstrap',
    nowIndicator: true,
    defaultTimedEventDuration: '00:20',
    headerToolbar: false,
    footerToolbar: false,
    height: 'auto',
    events: props.scheduleUrl,
    eventClick: onEventClick,
    eventDidMount: onEventDidMount,
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

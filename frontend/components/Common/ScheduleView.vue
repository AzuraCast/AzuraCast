<template>
    <full-calendar
        ref="$calendar"
        :options="calendarOptions"
    >
        <template
            v-for="(_, slot) of $slots"
            #[slot]="scope"
        >
            <slot
                :name="slot"
                v-bind="scope || {}"
            />
        </template>
    </full-calendar>
</template>

<script setup lang="ts">
import FullCalendar from "@fullcalendar/vue3";
import bootstrap5Plugin from "@fullcalendar/bootstrap5";
import allLocales from "@fullcalendar/core/locales-all";
import luxon3Plugin from "@fullcalendar/luxon3";
import timeGridPlugin from "@fullcalendar/timegrid";
import {computed, useTemplateRef} from "vue";
import {useAzuraCast} from "~/vendor/azuracast";
import {Calendar, CalendarOptions} from "@fullcalendar/core";

defineOptions({
    inheritAttrs: false
});

const props = defineProps<{
    options?: CalendarOptions
}>();

const $calendar = useTemplateRef('$calendar');

const getCalendarApi = (): Calendar => {
    if ($calendar.value) {
        return $calendar.value?.getApi();
    } else {
        throw new Error('Calendar unavailable');
    }
}

defineExpose({
    getCalendarApi
});

// Use the Bootstrap 5 theme, but revert some settings back to their defaults.
bootstrap5Plugin.themeClasses["bootstrap5"].prototype.baseIconClass = 'fc-icon';
bootstrap5Plugin.themeClasses["bootstrap5"].prototype.iconOverridePrefix = 'fc-';
bootstrap5Plugin.themeClasses["bootstrap5"].prototype.iconClasses = {
    close: 'fc-icon-x',
    prev: 'fc-icon-chevron-left',
    next: 'fc-icon-chevron-right',
    prevYear: 'fc-icon-chevrons-left',
    nextYear: 'fc-icon-chevrons-right',
}
bootstrap5Plugin.themeClasses["bootstrap5"].prototype.rtlIconClasses = {
    prev: 'fc-icon-chevron-right',
    next: 'fc-icon-chevron-left',
    prevYear: 'fc-icon-chevrons-right',
    nextYear: 'fc-icon-chevrons-left',
}

const {localeShort, timeConfig} = useAzuraCast();

const calendarOptions = computed<CalendarOptions>(() => {
    return {
        locale: localeShort,
        locales: allLocales,
        plugins: [luxon3Plugin, timeGridPlugin, bootstrap5Plugin],
        themeSystem: 'bootstrap5',
        initialView: 'timeGridWeek',
        nowIndicator: true,
        defaultTimedEventDuration: '00:20',
        headerToolbar: false,
        footerToolbar: false,
        height: 'auto',
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
        },
        ...props.options
    };
});
</script>

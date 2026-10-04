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
import bootstrap5Plugin from "@fullcalendar/bootstrap5";
import luxon3Plugin from "@fullcalendar/format-luxon3";
import FullCalendar, { CalendarApi, CalendarOptions } from "@fullcalendar/vue3";
import allLocales from "@fullcalendar/vue3/locales-all";
import timeGridPlugin from "@fullcalendar/vue3/timegrid";
import { computed, h, useTemplateRef } from "vue";
import { useAzuraCast } from "~/vendor/azuracast";
import IconIcChevronLeft from "~icons/ic/baseline-chevron-left";
import IconIcChevronRight from "~icons/ic/baseline-chevron-right";

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    options?: CalendarOptions;
}>();

const $calendar = useTemplateRef("$calendar");

const getCalendarApi = (): CalendarApi => {
    if ($calendar.value) {
        return $calendar.value?.getApi();
    } else {
        throw new Error("Calendar unavailable");
    }
};

defineExpose({
    getCalendarApi,
});

// Use the Bootstrap 5 theme, but revert some settings back to their defaults.
bootstrap5Plugin.optionDefaults.buttons = {
    prev: {
        iconContent: () => h(IconIcChevronLeft),
    },
    next: {
        iconContent: () => h(IconIcChevronRight),
    },
    prevYear: {
        iconContent: () => h(IconIcChevronLeft),
    },
    nextYear: {
        iconContent: () => h(IconIcChevronRight),
    },
};

const { localeShort, timeConfig } = useAzuraCast();

const calendarOptions = computed<CalendarOptions>(() => {
    return {
        locale: localeShort,
        locales: allLocales,
        plugins: [luxon3Plugin, timeGridPlugin, bootstrap5Plugin],
        initialView: "timeGridWeek",
        nowIndicator: true,
        defaultTimedEventDuration: "00:20",
        headerToolbar: false,
        footerToolbar: false,
        height: "auto",
        eventTimeFormat: {
            ...timeConfig,
            hour: "numeric",
            minute: "2-digit",
            meridiem: "short",
        },
        views: {
            timeGridWeek: {
                slotHeaderFormat: {
                    ...timeConfig,
                    hour: "numeric",
                    minute: "2-digit",
                    omitZeroMinute: true,
                    meridiem: "short",
                },
            },
        },
        ...props.options,
    };
});
</script>

<style>
@import "@fullcalendar/vue3/skeleton.css";
@import "@fullcalendar/bootstrap5/theme.css";
</style>

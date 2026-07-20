<template>
    <tab
        id="schedule_view"
        :label="$gettext('Schedule View')"
    >
        <div class="card-body-flush">
            <schedule
                ref="$schedule"
                :options="{
                    headerToolbar: {
                        left: 'prev,next',
                        center: 'title',
                        right: 'timeGridWeek,timeGridDay'
                    },
                    timeZone: timezone,
                    events: scheduleUrl,
                    eventClick: onClick,
                    eventMouseEnter: onMouseEnter,
                    eventMouseLeave: () => scheduleHide()
                }"
            >
                <template #eventContent="arg">
                    <div class="fc-event-main-frame schedule-event-content px-1">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <span class="d-inline-flex flex-shrink-0">
                                <icon-bi-exclamation-triangle-fill
                                    v-if="getEventProps(arg.event).has_group_schedule_conflict"
                                    class="text-warning flex-shrink-0 me-2"
                                    :class="IconSize.Small"
                                    :title="$gettext('This playlist is scheduled outside its group\'s active window and will not play at this time.')"
                                />

                                <span
                                    v-if="getEventProps(arg.event).type === 'streamer'"
                                    class="schedule-streamer-art d-inline-flex align-items-center justify-content-center overflow-hidden rounded-circle"
                                >
                                    <img
                                        v-if="getEventProps(arg.event).has_custom_art"
                                        :src="getEventProps(arg.event).art ?? undefined"
                                        class="w-100 h-100 object-fit-cover"
                                        alt="Streamer Artwork"
                                    >
                                    <icon-bi-mic-fill
                                        v-else
                                        :class="IconSize.Small"
                                    />
                                </span>
                                <playlist-source-icon
                                    v-else-if="getEventProps(arg.event).source"
                                    :source="getEventProps(arg.event).source!"
                                    :size="IconSize.Small"
                                />
                            </span>

                            <span
                                v-if="arg.timeText"
                                class="fc-event-time text-truncate"
                            >
                                {{ arg.timeText }}
                            </span>
                        </div>

                        <div class="fc-event-title fc-sticky text-center text-truncate">
                            {{ arg.event.title }}
                        </div>
                    </div>
                </template>
            </schedule>
        </div>

        <schedule-event-overlay
            :visible="overlayVisible"
            :referenceElement="overlayReferenceElement"
            :details="overlayDetails"
            @mouseenter="clearHideTimer()"
            @mouseleave="scheduleHide()"
        />
    </tab>
</template>

<script setup lang="ts">
import {
    Calendar,
    EventApi,
    EventClickArg,
    EventHoveringArg,
} from "@fullcalendar/core";
import { EventImpl } from "@fullcalendar/core/internal";
import { toRefs, useTimeoutFn } from "@vueuse/core";
import { ref, useTemplateRef } from "vue";
import Schedule from "~/components/Common/ScheduleView.vue";
import Tab from "~/components/Common/Tab.vue";
import PlaylistSourceIcon from "~/components/Stations/Common/PlaylistSourceIcon.vue";
import ScheduleEventOverlay from "~/components/Stations/Common/ScheduleEventOverlay.vue";
import { PlaylistSources } from "~/entities/ApiInterfaces.ts";
import type { ScheduleEventDetails } from "~/entities/StationSchedule.ts";
import { IconSize } from "~/functions/icons.ts";
import { useStationData } from "~/functions/useStationQuery.ts";
import IconBiExclamationTriangleFill from "~icons/bi/exclamation-triangle-fill";
import IconBiMicFill from "~icons/bi/mic-fill";

interface ScheduleEventCellProps {
    type?: ScheduleEventDetails["type"];
    source?: PlaylistSources;
    has_custom_art?: boolean;
    art?: string | null;
    has_group_schedule_conflict?: boolean;
}

defineProps<{
    scheduleUrl: string;
}>();

const emit = defineEmits<{
    click: [event: EventImpl];
}>();

const stationData = useStationData();
const { timezone } = toRefs(stationData);

const getEventProps = (event: EventApi): ScheduleEventCellProps =>
    event.extendedProps as ScheduleEventCellProps;

const onClick = (arg: EventClickArg) => {
    emit("click", arg.event);
};

const overlayVisible = ref<boolean>(false);
const overlayReferenceElement = ref<HTMLElement | null>(null);
const overlayDetails = ref<ScheduleEventDetails | null>(null);

const { start: scheduleHide, stop: clearHideTimer } = useTimeoutFn(
    () => {
        overlayVisible.value = false;
        overlayReferenceElement.value = null;
        overlayDetails.value = null;
    },
    150,
    { immediate: false },
);

const onMouseEnter = (arg: EventHoveringArg) => {
    const extendedProps = arg.event.extendedProps;

    if (!extendedProps.type) {
        return;
    }

    clearHideTimer();
    overlayReferenceElement.value = arg.el;
    overlayDetails.value = {
        ...extendedProps,
        name: arg.event.title,
    } as ScheduleEventDetails;
    overlayVisible.value = true;
};

const $schedule = useTemplateRef("$schedule");

const getCalendarApi = (): Calendar | undefined => {
    return $schedule.value?.getCalendarApi();
};

const refresh = () => getCalendarApi()?.refetchEvents();

defineExpose({
    getCalendarApi,
    refresh,
});
</script>

<style lang="scss" scoped>
@import "~/scss/_variables.scss";

.schedule-event-content {
    cursor: pointer;
}

.schedule-streamer-art {
    width: $icon-size-sm;
    height: $icon-size-sm;
}
</style>

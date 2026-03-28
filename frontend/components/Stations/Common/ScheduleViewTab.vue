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
                    eventClick: onClick
                }"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import Tab from "~/components/Common/Tab.vue";
import Schedule from "~/components/Common/ScheduleView.vue";
import {Calendar, EventClickArg} from "@fullcalendar/core";
import {EventImpl} from "@fullcalendar/core/internal";
import {useTemplateRef} from "vue";
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";

defineProps<{
    scheduleUrl: string
}>();

const emit = defineEmits<{
    click: [event: EventImpl]
}>();

const stationData = useStationData();
const {timezone} = toRefs(stationData);

const onClick = (arg: EventClickArg) => {
    emit('click', arg.event);
}

const $schedule = useTemplateRef('$schedule');

const getCalendarApi = (): Calendar | undefined => {
    return $schedule.value?.getCalendarApi();
};

const refresh = () => getCalendarApi()?.refetchEvents();

defineExpose({
    getCalendarApi,
    refresh
});
</script>

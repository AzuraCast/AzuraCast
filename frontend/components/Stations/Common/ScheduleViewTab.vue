<template>
    <tab id="schedule_view" :label="$gettext('Schedule View')">
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
import {useAzuraCastStation} from "~/vendor/azuracast.ts";
import {Calendar, EventClickArg} from "@fullcalendar/core";
import {EventImpl} from "@fullcalendar/core/internal";
import {ref} from "vue";

const props = defineProps<{
    scheduleUrl: string
}>();

const emit = defineEmits<{
    click: [event: EventImpl]
}>();

const {timezone} = useAzuraCastStation();

const onClick = (arg: EventClickArg) => {
    emit('click', arg.event);
}

const $schedule = ref<InstanceType<typeof Schedule> | null>();
const getCalendarApi = (): Calendar => $schedule.value?.getCalendarApi();
const refresh = () => getCalendarApi()?.refetchEvents();

defineExpose({
    getCalendarApi,
    refresh
});
</script>

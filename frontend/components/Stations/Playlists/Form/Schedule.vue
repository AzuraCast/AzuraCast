<template>
    <tab :label="$gettext('Schedule')">
        <form-markup
            v-if="scheduleItems.length === 0"
            id="no_scheduled_entries"
        >
            <template #label>
                {{ $gettext('Not Scheduled') }}
            </template>
            <p>
                {{
                    $gettext('This playlist currently has no scheduled times. It will play at all times. To add a new scheduled time, click the button below.')
                }}
            </p>
        </form-markup>

        <playlists-form-schedule-row
            v-for="(row, index) in scheduleItems"
            :key="index"
            v-model:row="scheduleItems[index]"
            :index="index"
            @remove="remove(index)"
        />

        <div class="buttons">
            <button
                type="button"
                class="btn btn-sm btn-primary"
                @click="add"
            >
                <icon-ic-add/>
                <span>
                    {{ $gettext('Add Schedule Item') }}
                </span>
            </button>
        </div>
    </tab>
</template>

<script setup lang="ts">
import PlaylistsFormScheduleRow from "~/components/Stations/Playlists/Form/ScheduleRow.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import Tab from "~/components/Common/Tab.vue";
import IconIcAdd from "~icons/ic/baseline-add";

const scheduleItems = defineModel<Array<any>>('scheduleItems', {
    default: () => []
})

const add = () => {
    scheduleItems.value.push({
        start_time: null,
        end_time: null,
        start_date: null,
        end_date: null,
        days: [],
        loop_once: false
    });
};

const remove = (index: number) => {
    scheduleItems.value.splice(index, 1);
};
</script>

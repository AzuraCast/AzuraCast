<template>
    <o-tab-item :labe="$gettext('Schedule')">
        <b-form-group v-if="scheduleItems.length === 0">
            <label>
                {{ $gettext('Not Scheduled') }}
            </label>
            <p>
                {{ $gettext('This streamer is not scheduled to play at any times.') }}
            </p>
        </b-form-group>

        <streamers-form-schedule-row
            v-for="(row, index) in scheduleItems"
            :key="index"
            v-model:row="scheduleItems[index]"
            :station-time-zone="stationTimeZone"
            :index="index"
            @remove="remove(index)"
        />

        <div class="buttons">
            <button
                class="btn btn-sm btn-primary"
                @click.prevent="add"
            >
                <icon icon="add" />
                <span>
                    {{ $gettext('Add Schedule Item') }}
                </span>
            </button>
        </div>
    </o-tab-item>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import StreamersFormScheduleRow from "~/components/Stations/Streamers/Form/ScheduleRow.vue";
import {useVModel} from "@vueuse/core";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    },
    scheduleItems: {
        type: Array,
        default: () => {
            return [];
        }
    }
});

const emit = defineEmits(['update:scheduleItems']);

const scheduleItems = useVModel(props, 'scheduleItems', emit);

const add = () => {
    scheduleItems.value.push({
        start_time: null,
        end_time: null,
        start_date: null,
        end_date: null,
        days: []
    });
};

const remove = (index) => {
    scheduleItems.value.splice(index, 1);
};
</script>

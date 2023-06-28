<template>
    <b-tab :title="$gettext('Schedule')">
        <b-form-group v-if="scheduleItems.length === 0">
            <label>
                {{ $gettext('Not Scheduled') }}
            </label>
            <p>
                {{
                    $gettext('This playlist currently has no scheduled times. It will play at all times. To add a new scheduled time, click the button below.')
                }}
            </p>
        </b-form-group>

        <playlists-form-schedule-row
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
    </b-tab>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import PlaylistsFormScheduleRow from "~/components/Stations/Playlists/Form/ScheduleRow.vue";
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
        days: [],
        loop_once: false
    });
};

const remove = (index) => {
    scheduleItems.value.splice(index, 1);
};
</script>

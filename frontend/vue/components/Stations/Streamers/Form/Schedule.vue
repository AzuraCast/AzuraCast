<template>
    <b-tab :title="$gettext('Schedule')">
        <b-form-group v-if="scheduleItems.length === 0">
            <label>
                {{ $gettext('Not Scheduled') }}
            </label>
            <p>
                {{ $gettext('This streamer is not scheduled to play at any times.') }}
            </p>
        </b-form-group>

        <streamers-form-schedule-row v-for="(row, index) in scheduleItems" :key="index"
                                     :station-time-zone="stationTimeZone"
                                     :index="index" v-model:row="scheduleItems[index]" @remove="remove(index)">
        </streamers-form-schedule-row>

        <b-button-group>
            <b-button size="sm" variant="outline-primary" @click.prevent="add">
                <icon icon="add"></icon>
                {{ $gettext('Add Schedule Item') }}
            </b-button>
        </b-button-group>
    </b-tab>
</template>

<script>
import PlaylistTime from '~/components/Common/TimeCode';
import Icon from '~/components/Common/Icon';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import StreamersFormScheduleRow from "~/components/Stations/Streamers/Form/ScheduleRow.vue";

export default {
    name: 'StreamerFormSchedule',
    components: {StreamersFormScheduleRow, BWrappedFormGroup, Icon, PlaylistTime},
    props: {
        form: Object,
        stationTimeZone: String,
        scheduleItems: Array
    },
    data() {
        return {
            dayOptions: [
                {value: 1, text: this.$gettext('Monday')},
                {value: 2, text: this.$gettext('Tuesday')},
                {value: 3, text: this.$gettext('Wednesday')},
                {value: 4, text: this.$gettext('Thursday')},
                {value: 5, text: this.$gettext('Friday')},
                {value: 6, text: this.$gettext('Saturday')},
                {value: 7, text: this.$gettext('Sunday')}
            ]
        };
    },
    methods: {
        add () {
            this.scheduleItems.push({
                start_time: null,
                end_time: null,
                start_date: null,
                end_date: null,
                days: []
            });
        },
        remove (index) {
            this.scheduleItems.splice(index, 1);
        }
    }
};
</script>

<template>
    <b-tab :title="langTabTitle">
        <b-form-group v-if="scheduleItems.length === 0">
            <label>
                <translate key="lang_schedule_not_scheduled">Not Scheduled</translate>
            </label>
            <p>
                <translate
                    key="lang_schedule_not_scheduled_desc">This streamer is not scheduled to play at any times.</translate>
            </p>
        </b-form-group>

        <streamers-form-schedule-row v-for="(row, index) in scheduleItems" :key="index"
                                     :station-time-zone="stationTimeZone"
                                     :index="index" :row.sync="row" @remove="remove(index)">
        </streamers-form-schedule-row>

        <b-button-group>
            <b-button size="sm" variant="outline-primary" @click.prevent="add">
                <icon icon="add"></icon>
                <translate key="lang_btn_add">Add Schedule Item</translate>
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
    computed: {
        langTabTitle () {
            return this.$gettext('Schedule');
        }
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

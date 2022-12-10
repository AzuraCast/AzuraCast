<template>
    <b-tab :title="langTabTitle">
        <b-form-group v-if="scheduleItems.length === 0">
            <label>
                <translate key="lang_playlist_schedule_not_scheduled">Not Scheduled</translate>
            </label>
            <p>
                <translate key="lang_playlist_schedule_desc">This playlist currently has no scheduled times. It will play at all times. To add a new scheduled time, click the button below.</translate>
            </p>
        </b-form-group>

        <playlists-form-schedule-row v-for="(row, index) in scheduleItems" :key="index"
                                     :station-time-zone="stationTimeZone"
                                     :index="index" v-model:row="scheduleItems[index]" @remove="remove(index)">
        </playlists-form-schedule-row>

        <b-button-group>
            <b-button size="sm" variant="outline-primary" @click.prevent="add">
                <icon icon="add"></icon>
                <translate key="lang_btn_add">Add Schedule Item</translate>
            </b-button>
        </b-button-group>
    </b-tab>
</template>

<script>
import Icon from '~/components/Common/Icon';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import PlaylistsFormScheduleRow from "~/components/Stations/Playlists/Form/ScheduleRow.vue";

export default {
    name: 'PlaylistEditSchedule',
    components: {PlaylistsFormScheduleRow, BWrappedFormCheckbox, BWrappedFormGroup, Icon},
    props: {
        form: Object,
        stationTimeZone: String,
        scheduleItems: Array
    },
    computed: {
        langTabTitle() {
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
                days: [],
                loop_once: false
            });
        },
        remove (index) {
            this.scheduleItems.splice(index, 1);
        }
    }
};
</script>

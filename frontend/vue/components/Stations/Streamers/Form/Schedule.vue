<template>
    <b-tab :title="langTabTitle">
        <b-form-group v-if="scheduleItems.length === 0">
            <label>
                <translate key="lang_schedule_not_scheduled">Not Scheduled</translate>
            </label>
            <p>
                <translate key="lang_schedule_not_scheduled_desc">This streamer is not scheduled to play at any times.</translate>
            </p>
        </b-form-group>

        <b-card v-for="(row, index) in form.schedule_items.$each.$iter" :key="index" class="mb-3" no-body>
            <div class="card-header bg-primary-dark d-flex align-items-center">
                <div class="flex-fill">
                    <h2 class="card-title">
                        <translate :key="'lang_scheduled_time_'+index" :translate-params="{ num: parseInt(index)+1 }">Scheduled Time #%{num}</translate>
                    </h2>
                </div>
                <div class="flex-shrink-0">
                    <b-button size="sm" variant="outline-light" class="py-2 pr-0" @click.prevent="remove(index)">
                        <icon icon="remove"></icon>
                        <translate key="lang_btn_remove">Remove</translate>
                    </b-button>
                </div>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-4" :id="'edit_form_start_time_'+index"
                                              :field="row.start_time">
                            <template #label="{lang}">
                                <translate :key="lang">Start Time</translate>
                            </template>
                            <template #default="props">
                                <playlist-time :id="props.id" v-model="props.field.$model"
                                               :state="props.state"></playlist-time>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-4" :id="'edit_form_end_time_'+index" :field="row.end_time">
                            <template #label="{lang}">
                                <translate :key="lang">End Time</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">If the end time is before the start time, the schedule entry will continue overnight.</translate>
                            </template>
                            <template #default="props">
                                <playlist-time :id="props.id" v-model="props.field.$model"
                                               :state="props.state"></playlist-time>
                            </template>
                        </b-wrapped-form-group>

                        <b-col md="4" class="form-group">
                            <label>
                                <translate key="lang_station_tz">Station Time Zone</translate>
                            </label>
                            <div>
                                <translate key="lang_station_tz_desc" :translate-params="{ tz: stationTimeZone }">This station's time zone is currently %{tz}.</translate>
                            </div>
                        </b-col>

                        <b-wrapped-form-group class="col-md-4" :id="'edit_form_start_date_'+index"
                                              :field="row.start_date" input-type="date">
                            <template #label="{lang}">
                                <translate :key="lang">Start Date</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">To set this schedule to run only within a certain date range, specify a start and end date.</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-4" :id="'edit_form_end_date_'+index" :field="row.end_date"
                                              input-type="date">
                            <template #label="{lang}">
                                <translate :key="lang">End Date</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-4" :id="'edit_form_days_'+index" :field="row.days">
                            <template #label="{lang}">
                                <translate :key="lang">Scheduled Play Days of Week</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">Leave blank to play on every day of the week.</translate>
                            </template>
                            <template #default="props">
                                <b-checkbox-group stacked :id="props.id" v-model="props.field.$model"
                                                  :options="dayOptions"></b-checkbox-group>
                            </template>
                        </b-wrapped-form-group>

                    </b-form-row>
                </b-form-group>
            </b-card-body>
        </b-card>

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

export default {
    name: 'StreamerFormSchedule',
    components: {BWrappedFormGroup, Icon, PlaylistTime},
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

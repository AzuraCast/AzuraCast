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

        <b-card v-for="(row, index) in form.schedule_items.$each.$iter" :key="index" class="mb-3" no-body>
            <div class="card-header bg-primary-dark d-flex align-items-center">
                <div class="flex-fill">
                    <h2 class="card-title">
                        <translate :key="'lang_schedule_entry_'+index" :translate-params="{ num: parseInt(index)+1 }">Scheduled Time #%{num}</translate>
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
                    <b-row>
                        <b-form-group class="col-md-4" :label-for="'edit_form_start_time_'+index">
                            <template v-slot:label>
                                <translate key="lang_form_start_time">Start Time</translate>
                            </template>
                            <template v-slot:description>
                                <translate key="lang_form_start_time_desc">To play once per day, set the start and end times to the same value.</translate>
                            </template>
                            <playlist-time :id="'edit_form_start_time_'+index" v-model="row.start_time.$model"
                                           :state="row.start_time.$dirty ? !row.start_time.$error : null"></playlist-time>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>
                        <b-form-group class="col-md-4" :label-for="'edit_form_end_time_'+index">
                            <template v-slot:label>
                                <translate key="lang_form_end_time">End Time</translate>
                            </template>
                            <template v-slot:description>
                                <translate key="lang_form_end_time_desc">If the end time is before the start time, the playlist will play overnight.</translate>
                            </template>
                            <playlist-time :id="'edit_form_end_time_'+index" v-model="row.end_time.$model"
                                           :state="row.end_time.$dirty ? !row.end_time.$error : null"></playlist-time>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>
                        <b-col md="4" class="form-group">
                            <label>
                                <translate key="lang_station_tz">Station Time Zone</translate>
                            </label>
                            <div>
                                <translate key="lang_station_tz_desc" :translate-params="{ tz: stationTimeZone }">This station's time zone is currently %{tz}.</translate>
                            </div>
                        </b-col>

                        <b-form-group class="col-md-4" :label-for="'edit_form_start_date_'+index">
                            <template v-slot:label>
                                <translate key="lang_form_start_date">Start Date</translate>
                            </template>
                            <template v-slot:description>
                                <translate key="lang_form_start_date_desc">To set this schedule to run only within a certain date range, specify a start and end date.</translate>
                                <div v-if="hasAdvancedSettings" class="text-danger">
                                    <translate key="lang_form_start_date_warning">Start/end date cannot be used on playlists with advanced settings!</translate>
                                </div>
                            </template>
                            <b-form-input :label-for="'edit_form_start_date_'+index" type="date"
                                          :disabled="hasAdvancedSettings"
                                          v-model="row.start_date.$model"
                                          :state="row.start_date.$dirty ? !row.start_date.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-4" :label-for="'edit_form_end_date_'+index">
                            <template v-slot:label>
                                <translate key="lang_form_end_date">End Date</translate>
                            </template>
                            <b-form-input :label-for="'edit_form_end_date_'+index" type="date"
                                          v-model="row.end_date.$model"
                                          :disabled="hasAdvancedSettings"
                                          :state="row.end_date.$dirty ? !row.end_date.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-4" :label-for="'edit_form_loop_once_'+index">
                            <template v-slot:description>
                                <translate key="lang_form_loop_once_desc">Only loop through playlist once.</translate>
                            </template>
                            <b-form-checkbox :id="'edit_form_loop_once_'+index" v-model="row.loop_once.$model">
                                <translate key="lang_form_loop_once">Loop Once</translate>
                            </b-form-checkbox>
                        </b-form-group>

                        <b-form-group class="col-md-4" :label-for="'edit_form_days_'+index">
                            <template v-slot:label>
                                <translate key="lang_form_days">Scheduled Play Days of Week</translate>
                            </template>
                            <template v-slot:description>
                                <translate key="lang_form_days_desc">Leave blank to play on every day of the week.</translate>
                            </template>

                            <b-checkbox-group stacked :id="'edit_form_days_'+index" v-model="row.days.$model"
                                              :options="dayOptions"></b-checkbox-group>
                        </b-form-group>
                    </b-row>
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
import PlaylistTime from '../../../Common/TimeCode';
import Icon from '../../../Common/Icon';

export default {
    name: 'PlaylistEditSchedule',
    components: { Icon, PlaylistTime },
    props: {
        form: Object,
        stationTimeZone: String,
        scheduleItems: Array
    },
    data () {
        return {
            dayOptions: [
                { value: 1, text: this.$gettext('Monday') },
                { value: 2, text: this.$gettext('Tuesday') },
                { value: 3, text: this.$gettext('Wednesday') },
                { value: 4, text: this.$gettext('Thursday') },
                { value: 5, text: this.$gettext('Friday') },
                { value: 6, text: this.$gettext('Saturday') },
                { value: 7, text: this.$gettext('Sunday') }
            ]
        };
    },
    computed: {
        langTabTitle () {
            return this.$gettext('Schedule');
        },
        hasAdvancedSettings () {
            return this.form.backend_options.$model.length > 1;
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

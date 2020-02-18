<template>
    <b-tab :title="langTabTitle">
        <b-form-group v-if="scheduleItems.length === 0">
            <label>
                <translate>Not Scheduled</translate>
            </label>
            <p>
                <translate>This streamer is not scheduled to play at any times.</translate>
            </p>
        </b-form-group>

        <b-card v-for="(row, index) in form.schedule_items.$each.$iter" :key="index" class="mb-3" no-body>
            <div class="card-header bg-primary-dark d-flex align-items-center">
                <div class="flex-fill">
                    <h2 class="card-title">
                        <translate :translate-params="{ num: parseInt(index)+1 }">Scheduled Time #%{num}</translate>
                    </h2>
                </div>
                <div class="flex-shrink-0">
                    <b-button size="sm" variant="outline-light" class="py-1 pr-0" @click.prevent="remove(index)">
                        <i class="material-icons" aria-hidden="true">remove</i>
                        <translate>Remove</translate>
                    </b-button>
                </div>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-row>
                        <b-form-group class="col-md-4" :label-for="'edit_form_start_time_'+index">
                            <template v-slot:label>
                                <translate>Start Time</translate>
                            </template>
                            <playlist-time :id="'edit_form_start_time_'+index" v-model="row.start_time.$model"
                                           :state="row.start_time.$dirty ? !row.start_time.$error : null"></playlist-time>
                            <b-form-invalid-feedback>
                                <translate>This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>
                        <b-form-group class="col-md-4" :label-for="'edit_form_end_time_'+index">
                            <template v-slot:label>
                                <translate>End Time</translate>
                            </template>
                            <template v-slot:description>
                                <translate>If the end time is before the start time, the schedule entry will continue overnight.</translate>
                            </template>
                            <playlist-time :id="'edit_form_end_time_'+index" v-model="row.end_time.$model"
                                           :state="row.end_time.$dirty ? !row.end_time.$error : null"></playlist-time>
                            <b-form-invalid-feedback>
                                <translate>This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>
                        <b-col md="4" class="form-group">
                            <label>
                                <translate>Station Time Zone</translate>
                            </label>
                            <div>
                                <translate :translate-params="{ tz: stationTimeZone }">This station's time zone is currently %{tz}.</translate>
                            </div>
                        </b-col>

                        <b-form-group class="col-md-4" :label-for="'edit_form_start_date_'+index">
                            <template v-slot:label>
                                <translate>Start Date</translate>
                            </template>
                            <template v-slot:description>
                                <translate>To set this schedule to run only within a certain date range, specify a start and end date.</translate>
                            </template>
                            <b-form-input :label-for="'edit_form_start_date_'+index" type="date"
                                          v-model="row.start_date.$model"
                                          :state="row.start_date.$dirty ? !row.start_date.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate>This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-4" :label-for="'edit_form_end_date_'+index">
                            <template v-slot:label>
                                <translate>End Date</translate>
                            </template>
                            <b-form-input :label-for="'edit_form_end_date_'+index" type="date"
                                          v-model="row.end_date.$model"
                                          :state="row.end_date.$dirty ? !row.end_date.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate>This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-4" :label-for="'edit_form_days_'+index">
                            <template v-slot:label>
                                <translate>Scheduled Play Days of Week</translate>
                            </template>
                            <template v-slot:description>
                                <translate>Leave blank to play on every day of the week.</translate>
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
                <i class="material-icons" aria-hidden="true">add</i>
                <translate>Add Schedule Item</translate>
            </b-button>
        </b-button-group>
    </b-tab>
</template>

<script>
    import PlaylistTime from '../../components/TimeCode';

    export default {
        name: 'StreamerFormSchedule',
        components: { PlaylistTime },
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
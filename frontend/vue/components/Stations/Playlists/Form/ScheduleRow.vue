<template>
    <b-card class="mb-3" no-body>
        <div class="card-header bg-primary-dark d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ $gettext('Scheduled Time #%{num}') }}
                </h2>
            </div>
            <div class="flex-shrink-0">
                <b-button size="sm" variant="outline-light" class="py-2 pr-0" @click.prevent="$emit('remove')">
                    <icon icon="remove"></icon>
                    {{ $gettext('Remove') }}
                </b-button>
            </div>
        </div>
        <b-card-body>
            <b-form-group>
                <b-form-row>
                    <b-wrapped-form-group class="col-md-4" :id="'edit_form_start_time_'+index"
                                          :field="v$.row.start_time">
                        <template #label="{lang}">
                            {{ $gettext('Start Time') }}
                        </template>
                        <template #description="{lang}">
                            {{ $gettext('To play once per day, set the start and end times to the same value.') }}.00
                        </template>
                        <template #default="props">
                            <playlist-time :id="props.id" v-model="props.field.$model"
                                           :state="props.state"></playlist-time>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-4" :id="'edit_form_end_time_'+index" :field="v$.row.end_time">
                        <template #label="{lang}">
                            {{ $gettext('End Time') }}
                        </template>
                        <template #description="{lang}">
                            {{
                                $gettext('If the end time is before the start time, the playlist will play overnight.')
                            }}
                        </template>
                        <template #default="props">
                            <playlist-time :id="props.id" v-model="props.field.$model"
                                           :state="props.state"></playlist-time>
                        </template>
                    </b-wrapped-form-group>

                    <b-col md="4" class="form-group">
                        <label>
                            {{ $gettext('Station Time Zone') }}
                        </label>
                        <div>
                            {{ $gettext('This station\'s time zone is currently %{tz}.', {tz: stationTimeZone}) }}
                        </div>
                    </b-col>

                    <b-wrapped-form-group class="col-md-4" :id="'edit_form_start_date_'+index"
                                          :field="v$.row.start_date">
                        <template #label="{lang}">
                            {{ $gettext('Start Date') }}
                        </template>
                        <template #description="{lang}">
                            {{
                                $gettext('To set this schedule to run only within a certain date range, specify a start and end date.')
                            }}
                        </template>
                        <template #default="props">
                            <b-form-input :id="props.id" type="date"
                                          v-model="props.field.$model" :state="props.state"></b-form-input>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-4" :id="'edit_form_end_date_'+index" :field="v$.row.end_date">
                        <template #label="{lang}">
                            {{ $gettext('End Date') }}
                        </template>
                        <template #default="props">
                            <b-form-input :id="props.id" type="date" v-model="props.field.$model"
                                          :state="props.state"></b-form-input>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-checkbox class="col-md-4" :id="'edit_form_loop_once_'+index"
                                             :field="v$.row.loop_once">
                        <template #label="{lang}">
                            {{ $gettext('Loop Once') }}
                        </template>
                        <template #description="{lang}">
                            {{ $gettext('Only loop through playlist once.') }}
                        </template>
                    </b-wrapped-form-checkbox>

                    <b-wrapped-form-group class="col-md-4" :id="'edit_form_days_'+index" :field="v$.row.days">
                        <template #label="{lang}">
                            {{ $gettext('Scheduled Play Days of Week') }}
                        </template>
                        <template #description="{lang}">
                            {{ $gettext('Leave blank to play on every day of the week.') }}
                        </template>
                        <template #default="props">
                            <b-checkbox-group stacked :id="'edit_form_days_'+index" v-model="v$.row.days.$model"
                                              :options="dayOptions"></b-checkbox-group>
                        </template>
                    </b-wrapped-form-group>

                </b-form-row>
            </b-form-group>
        </b-card-body>
    </b-card>
</template>

<script>
import PlaylistTime from '~/components/Common/TimeCode';
import Icon from "~/components/Common/Icon.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {required} from "@vuelidate/validators";
import useVuelidate from "@vuelidate/core";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox.vue";

export default {
    name: 'PlaylistsFormScheduleRow',
    components: {BWrappedFormCheckbox, BWrappedFormGroup, Icon, PlaylistTime},
    props: {
        index: Number,
        row: Object,
        stationTimeZone: String,
    },
    setup() {
        return {v$: useVuelidate()}
    },
    emits: ['remove'],
    validations: {
        row: {
            'start_time': {required},
            'end_time': {required},
            'start_date': {},
            'end_date': {},
            'days': {},
            'loop_once': {}
        }
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
}
</script>

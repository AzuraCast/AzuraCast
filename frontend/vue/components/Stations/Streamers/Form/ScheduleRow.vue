<template>
    <b-card
        class="mb-3"
        no-body
    >
        <div class="card-header bg-primary-dark d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ $gettext('Scheduled Time #%{num}', {num: index + 1}) }}
                </h2>
            </div>
            <div class="flex-shrink-0">
                <b-button
                    size="sm"
                    variant="outline-light"
                    class="py-2 pr-0"
                    @click.prevent="doRemove()"
                >
                    <icon icon="remove" />
                    {{ $gettext('Remove') }}
                </b-button>
            </div>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
                    <b-wrapped-form-group
                        :id="'edit_form_start_time_'+index"
                        class="col-md-4"
                        :field="v$.start_time"
                    >
                        <template #label>
                            {{ $gettext('Start Time') }}
                        </template>
                        <template #default="slotProps">
                            <playlist-time
                                :id="slotProps.id"
                                v-model="slotProps.field.$model"
                                :state="slotProps.state"
                            />
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        :id="'edit_form_end_time_'+index"
                        class="col-md-4"
                        :field="v$.end_time"
                    >
                        <template #label>
                            {{ $gettext('End Time') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('If the end time is before the start time, the schedule entry will continue overnight.')
                            }}
                        </template>
                        <template #default="slotProps">
                            <playlist-time
                                :id="slotProps.id"
                                v-model="slotProps.field.$model"
                                :state="slotProps.state"
                            />
                        </template>
                    </b-wrapped-form-group>

                    <b-col
                        md="4"
                        class="form-group"
                    >
                        <label>
                            {{ $gettext('Station Time Zone') }}
                        </label>
                        <div>
                            {{ $gettext('This station\'s time zone is currently %{tz}.', {tz: stationTimeZone}) }}
                        </div>
                    </b-col>

                    <b-wrapped-form-group
                        :id="'edit_form_start_date_'+index"
                        class="col-md-4"
                        :field="v$.start_date"
                        input-type="date"
                    >
                        <template #label>
                            {{ $gettext('Start Date') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('To set this schedule to run only within a certain date range, specify a start and end date.')
                            }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        :id="'edit_form_end_date_'+index"
                        class="col-md-4"
                        :field="v$.end_date"
                        input-type="date"
                    >
                        <template #label>
                            {{ $gettext('End Date') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        :id="'edit_form_days_'+index"
                        class="col-md-4"
                        :field="v$.days"
                    >
                        <template #label>
                            {{ $gettext('Scheduled Play Days of Week') }}
                        </template>
                        <template #description>
                            {{ $gettext('Leave blank to play on every day of the week.') }}
                        </template>
                        <template #default="slotProps">
                            <b-checkbox-group
                                :id="slotProps.id"
                                v-model="slotProps.field.$model"
                                stacked
                                :options="dayOptions"
                            />
                        </template>
                    </b-wrapped-form-group>
                </div>
            </b-form-group>
        </b-card-body>
    </b-card>
</template>

<script setup>
import PlaylistTime from '~/components/Common/TimeCode';
import Icon from "~/components/Common/Icon.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {required} from "@vuelidate/validators";
import useVuelidate from "@vuelidate/core";
import {toRef} from "vue";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    index: {
        type: Number,
        required: true
    },
    row: {
        type: Object,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    },
});

const emit = defineEmits(['remove']);

const v$ = useVuelidate(
    {
        'start_time': {required},
        'end_time': {required},
        'start_date': {},
        'end_date': {},
        'days': {}
    },
    toRef(props, 'row')
);

const {$gettext} = useTranslate();

const dayOptions = [
    {value: 1, text: $gettext('Monday')},
    {value: 2, text: $gettext('Tuesday')},
    {value: 3, text: $gettext('Wednesday')},
    {value: 4, text: $gettext('Thursday')},
    {value: 5, text: $gettext('Friday')},
    {value: 6, text: $gettext('Saturday')},
    {value: 7, text: $gettext('Sunday')}
];

const doRemove = () => {
    emit('remove');
};
</script>

<template>
    <div class="card mb-3">
        <div class="card-header text-bg-primary d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ $gettext('Scheduled Time #%{num}', {num: index + 1}) }}
                </h2>
            </div>
            <div class="flex-shrink-0">
                <button
                    type="button"
                    class="btn btn-sm btn-dark"
                    @click="doRemove()"
                >
                    <icon-ic-remove/>

                    <span>
                        {{ $gettext('Remove') }}
                    </span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <form-group-field
                    :id="'edit_form_start_time_'+index"
                    class="col-md-4"
                    :field="r$.start_time"
                >
                    <template #label>
                        {{ $gettext('Start Time') }}
                    </template>
                    <template #default="{id, model}">
                        <playlist-time
                            :id="id"
                            v-model="model.$model"
                        />
                    </template>
                </form-group-field>

                <form-group-field
                    :id="'edit_form_end_time_'+index"
                    class="col-md-4"
                    :field="r$.end_time"
                >
                    <template #label>
                        {{ $gettext('End Time') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('If the end time is before the start time, the schedule entry will continue overnight.')
                        }}
                    </template>
                    <template #default="{id, model}">
                        <playlist-time
                            :id="id"
                            v-model="model.$model"
                        />
                    </template>
                </form-group-field>

                <form-markup
                    id="station_time_zone"
                    class="col-md-4"
                >
                    <template #label>
                        {{ $gettext('Station Time Zone') }}
                    </template>

                    <time-zone />
                </form-markup>

                <form-group-field
                    :id="'edit_form_start_date_'+index"
                    class="col-md-4"
                    :field="r$.start_date"
                    input-type="date"
                    :label="$gettext('Start Date')"
                    :description="$gettext('To set this schedule to run only within a certain date range, specify a start and end date.')"
                />

                <form-group-field
                    :id="'edit_form_end_date_'+index"
                    class="col-md-4"
                    :field="r$.end_date"
                    input-type="date"
                    :label="$gettext('End Date')"
                />

                <form-group-multi-check
                    :id="'edit_form_days_'+index"
                    class="col-md-4"
                    :field="r$.days"
                    :options="dayOptions"
                    stacked
                    :label="$gettext('Scheduled Play Days of Week')"
                    :description="$gettext('Leave blank to play on every day of the week.')"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import PlaylistTime from "~/components/Common/TimeCode.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {required} from "@regle/rules";
import {toRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import TimeZone from "~/components/Stations/Common/TimeZone.vue";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import IconIcRemove from "~icons/ic/baseline-remove";

interface PlaylistScheduleRow {
    start_time: number,
    end_time: number,
    start_date: string,
    end_date: string,
    days: number[],
}

const props = defineProps<{
    index: number,
    row: PlaylistScheduleRow,
}>();

const emit = defineEmits<{
    (e: 'remove'): void
}>();

const {r$} = useAppScopedRegle(
    toRef(props, 'row'),
    {
        start_time: {required},
        end_time: {required},
    },
    {
        namespace: 'stations-streamers'
    }
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

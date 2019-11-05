<template>
    <b-tab :title="langTabTitle">
        <b-row>
            <b-col md="12">
                <b-form-group label-for="edit_form_is_enabled">
                    <template v-slot:label v-translate>
                        Is Enabled
                    </template>
                    <template v-slot:description v-translate>
                        If set to "No", the playlist will not be included in radio playback, but can still be
                        managed.
                    </template>
                    <b-form-checkbox v-model="form.is_enabled" id="edit_form_is_enabled" switch v-translate>
                        Is Enabled
                    </b-form-checkbox>
                </b-form-group>
            </b-col>
            <b-col md="6">
                <b-form-group label-for="edit_form_name">
                    <template v-slot:label v-translate>
                        Playlist Name
                    </template>
                    <b-form-input type="text" id="edit_form_name" v-model="form.name.$model"
                                  :state="form.name.$dirty ? !form.name.$error : null"></b-form-input>
                    <b-form-invalid-feedback v-translate>
                        This field is required.
                    </b-form-invalid-feedback>
                </b-form-group>
            </b-col>
        </b-row>

        'schedule_start_time' => [
        'PlaylistTime', // Custom form field
        [
        'label' => __('Start Time'),
        'description' => __('To play once per day, set the start and end times to the same value.'),
        'form_group_class' => 'col-md-3',
        ],
        ],

        'schedule_end_time' => [
        'PlaylistTime',
        [
        'label' => __('End Time'),
        'description' => __('If the end time is before the start time, the playlist will play overnight.'),
        'form_group_class' => 'col-md-3',
        ],
        ],

        'station_time_zone' => [
        'markup',
        [
        'label' => __('Station Time Zone'),
        'description' => '',
        'form_group_class' => 'col-md-6',
        ],
        ],

        'schedule_days' => [
        'checkbox',
        [
        'label' => __('Scheduled Play Days of Week'),
        'description' => __('Leave blank to play on every day of the week.'),
        'choices' => [
        1 => __('Monday'),
        2 => __('Tuesday'),
        3 => __('Wednesday'),
        4 => __('Thursday'),
        5 => __('Friday'),
        6 => __('Saturday'),
        7 => __('Sunday'),
        ],
        'form_group_class' => 'col-md-12',
        ],
        ],

    </b-tab>
</template>

<script>
  export default {
    name: 'PlaylistEditSchedule',
    props: {
      form: Object
    },
    computed: {
      langTabTitle () {
        return this.$gettext('Schedule')
      }
    }
  }
</script>
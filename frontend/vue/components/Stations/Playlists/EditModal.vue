<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <b-tabs content-class="mt-3">
            <form-basic-info :form="$v.form"></form-basic-info>
            <form-schedule :form="$v.form" :schedule-items="form.schedule_items"
                           :station-time-zone="stationTimeZone"></form-schedule>
            <form-advanced :form="$v.form" v-if="enableAdvancedFeatures"></form-advanced>
        </b-tabs>

    </modal-form>
</template>

<script>
import {required} from 'vuelidate/dist/validators.min.js';
import FormBasicInfo from './Form/BasicInfo';
import FormSchedule from './Form/Schedule';
import FormAdvanced from './Form/Advanced';
import BaseEditModal from '~/components/Common/BaseEditModal';

export default {
    name: 'EditModal',
    emits: ['needs-restart'],
    components: {FormSchedule, FormBasicInfo, FormAdvanced},
    mixins: [BaseEditModal],
    props: {
        stationTimeZone: String,
        enableAdvancedFeatures: Boolean
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Playlist')
                : this.$gettext('Add Playlist');
        }
    },
    validations: {
        form: {
            'name': {required},
            'is_enabled': {},
            'include_in_on_demand': {},
            'weight': {},
            'type': {},
            'source': {},
            'order': {},
            'remote_url': {},
            'remote_type': {},
            'remote_buffer': {},
            'is_jingle': {},
            'play_per_songs': {},
            'play_per_minutes': {},
            'play_per_hour_minute': {},
            'include_in_requests': {},
            'avoid_duplicates': {},
            'backend_options': {},
            'schedule_items': {
                $each: {
                    'start_time': {required},
                    'end_time': {required},
                    'start_date': {},
                    'end_date': {},
                    'days': {},
                    'loop_once': {}
                }
            }
        }
    },
    methods: {
        resetForm() {
            this.form = {
                'name': '',
                'is_enabled': true,
                'include_in_on_demand': false,
                'weight': 3,
                'type': 'default',
                'source': 'songs',
                'order': 'shuffle',
                'remote_url': null,
                'remote_type': 'stream',
                'remote_buffer': 0,
                'is_jingle': false,
                'play_per_songs': 0,
                'play_per_minutes': 0,
                'play_per_hour_minute': 0,
                'include_in_requests': true,
                'avoid_duplicates': true,
                'backend_options': [],
                'schedule_items': []
            };
        },
        onSubmitSuccess(response) {
            this.$notifySuccess();

            this.$emit('needs-restart');
            this.$emit('relist');

            this.close();
        },
    }
};
</script>

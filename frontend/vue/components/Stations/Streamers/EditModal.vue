<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <b-tabs content-class="mt-3">
            <form-basic-info :form="$v.form"></form-basic-info>
            <form-schedule :form="$v.form" :schedule-items="form.schedule_items"
                           :station-time-zone="stationTimeZone"></form-schedule>
        </b-tabs>

    </modal-form>
</template>
<script>
import {required} from 'vuelidate/dist/validators.min.js';
import FormBasicInfo from './Form/BasicInfo';
import FormSchedule from './Form/Schedule';
import BaseEditModal from '~/components/Common/BaseEditModal';

export default {
    name: 'EditModal',
    mixins: [BaseEditModal],
    components: {FormBasicInfo, FormSchedule},
    props: {
        stationTimeZone: String
    },
    validations() {
        let validations = {
            form: {
                'streamer_username': {required},
                'streamer_password': {},
                'display_name': {},
                'comments': {},
                'is_active': {},
                'enforce_schedule': {},
                'schedule_items': {
                    $each: {
                        'start_time': { required },
                        'end_time': { required },
                        'start_date': {},
                        'end_date': {},
                        'days': {}
                    }
                }
            }
        };

        if (this.editUrl === null) {
            validations.form.streamer_password = { required };
        }

        return validations;
    },
    computed: {
        langTitle () {
            return this.isEditMode
                ? this.$gettext('Edit Streamer')
                : this.$gettext('Add Streamer');
        }
    },
    methods: {
        resetForm () {
            this.form = {
                'streamer_username': null,
                'streamer_password': null,
                'display_name': null,
                'comments': null,
                'is_active': true,
                'enforce_schedule': false,
                'schedule_items': []
            };
        }
    }
};
</script>

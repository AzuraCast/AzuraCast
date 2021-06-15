<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-overlay variant="card" :show="loading">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>
            <b-form class="form" @submit.prevent="doSubmit">
                <b-tabs content-class="mt-3">
                    <form-basic-info :form="$v.form"></form-basic-info>
                    <form-schedule :form="$v.form" :schedule-items="form.schedule_items"
                                   :station-time-zone="stationTimeZone"></form-schedule>
                </b-tabs>

                <invisible-submit-button/>
            </b-form>
        </b-overlay>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import required from 'vuelidate/src/validators/required';
import FormBasicInfo from './Form/BasicInfo';
import FormSchedule from './Form/Schedule';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import BaseEditModal from '../../Common/BaseEditModal';

export default {
    name: 'EditModal',
    mixins: [BaseEditModal],
    components: { FormBasicInfo, FormSchedule, InvisibleSubmitButton },
    props: {
        stationTimeZone: String
    },
    validations () {
        let validations = {
            form: {
                'streamer_username': { required },
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
        },
        populateForm (d) {
            this.form = {
                'streamer_username': d.streamer_username,
                'streamer_password': null,
                'display_name': d.display_name,
                'comments': d.comments,
                'is_active': d.is_active,
                'enforce_schedule': d.enforce_schedule,
                'schedule_items': d.schedule_items
            };
        }
    }
};
</script>

<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-overlay variant="card" :show="loading">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>
            <b-form class="form" @submit.prevent="doSubmit">
                <b-tabs content-class="mt-3">
                    <form-basic-info :form="$v.form"></form-basic-info>
                    <form-source :form="$v.form"></form-source>
                    <form-schedule :form="$v.form" :schedule-items="form.schedule_items"
                                   :station-time-zone="stationTimeZone"></form-schedule>
                    <form-advanced :form="$v.form" v-if="enableAdvancedFeatures"></form-advanced>
                </b-tabs>

                <invisible-submit-button/>
            </b-form>
        </b-overlay>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save_changes">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import required from 'vuelidate/src/validators/required';
import FormBasicInfo from './Form/BasicInfo';
import FormSource from './Form/Source';
import FormSchedule from './Form/Schedule';
import FormAdvanced from './Form/Advanced';
import BaseEditModal from '../../Common/BaseEditModal';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';

export default {
    name: 'EditModal',
    components: { FormSchedule, FormSource, FormBasicInfo, FormAdvanced, InvisibleSubmitButton },
    mixins: [BaseEditModal],
    props: {
        stationTimeZone: String,
        enableAdvancedFeatures: Boolean
    },
    computed: {
        langTitle () {
            return this.isEditMode
                ? this.$gettext('Edit Playlist')
                : this.$gettext('Add Playlist');
        }
    },
    validations: {
        form: {
            'name': { required },
            'is_enabled': { required },
            'include_in_on_demand': {},
            'weight': { required },
            'type': { required },
            'source': { required },
            'order': { required },
            'remote_url': {},
            'remote_type': {},
            'remote_buffer': {},
            'is_jingle': {},
            'play_per_songs': {},
            'play_per_minutes': {},
            'play_per_hour_minute': {},
            'include_in_requests': {},
            'include_in_automation': {},
            'avoid_duplicates': {},
            'backend_options': {},
            'schedule_items': {
                $each: {
                    'start_time': { required },
                    'end_time': { required },
                    'start_date': {},
                    'end_date': {},
                    'days': {},
                    'loop_once': {}
                }
            }
        }
    },
    methods: {
        resetForm () {
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
                'include_in_automation': false,
                'avoid_duplicates': true,
                'backend_options': [],
                'schedule_items': []
            };
        },
        populateForm (d) {
            this.form = {
                'name': d.name,
                'is_enabled': d.is_enabled,
                'include_in_on_demand': d.include_in_on_demand,
                'weight': d.weight,
                'type': d.type,
                'source': d.source,
                'order': d.order,
                'remote_url': d.remote_url,
                'remote_type': d.remote_type,
                'remote_buffer': d.remote_buffer,
                'is_jingle': d.is_jingle,
                'play_per_songs': d.play_per_songs,
                'play_per_minutes': d.play_per_minutes,
                'play_per_hour_minute': d.play_per_hour_minute,
                'include_in_requests': d.include_in_requests,
                'include_in_automation': d.include_in_automation,
                'avoid_duplicates': d.avoid_duplicates,
                'backend_options': d.backend_options,
                'schedule_items': d.schedule_items
            };
        }
    }
};
</script>

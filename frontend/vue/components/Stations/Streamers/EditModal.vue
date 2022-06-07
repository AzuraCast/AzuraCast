<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <b-tabs content-class="mt-3">
            <form-basic-info :form="$v.form"></form-basic-info>
            <form-schedule :form="$v.form" :schedule-items="form.schedule_items"
                           :station-time-zone="stationTimeZone"></form-schedule>
            <form-artwork v-model="$v.form.artwork_file.$model" :artwork-src="record.links.art"
                          :new-art-url="newArtUrl" :edit-art-url="record.links.art"></form-artwork>
        </b-tabs>

    </modal-form>
</template>
<script>
import {required} from 'vuelidate/dist/validators.min.js';
import FormBasicInfo from './Form/BasicInfo';
import FormSchedule from './Form/Schedule';
import FormArtwork from './Form/Artwork';
import BaseEditModal from '~/components/Common/BaseEditModal';
import mergeExisting from "~/functions/mergeExisting";

export default {
    name: 'EditModal',
    mixins: [BaseEditModal],
    components: {FormBasicInfo, FormSchedule, FormArtwork},
    props: {
        stationTimeZone: String,
        newArtUrl: String,
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
                'artwork_file': {},
                'schedule_items': {
                    $each: {
                        'start_time': {required},
                        'end_time': {required},
                        'start_date': {},
                        'end_date': {},
                        'days': {}
                    }
                }
            }
        };

        if (this.editUrl === null) {
            validations.form.streamer_password = {required};
        }

        return validations;
    },
    data() {
        return {
            record: {
                has_custom_art: false,
                links: {}
            },
        }
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Streamer')
                : this.$gettext('Add Streamer');
        }
    },
    methods: {
        resetForm() {
            this.form = {
                'streamer_username': null,
                'streamer_password': null,
                'display_name': null,
                'comments': null,
                'is_active': true,
                'enforce_schedule': false,
                'schedule_items': [],
                'artwork_file': null
            };
        },
        populateForm(d) {
            this.record = d;
            this.form = mergeExisting(this.form, d);
        }
    }
};
</script>

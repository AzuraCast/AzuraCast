<template>
    <modal-form
        ref="modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.form.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <b-tabs
            content-class="mt-3"
            pills
        >
            <form-basic-info :form="v$.form" />
            <form-schedule
                v-model:schedule-items="form.schedule_items"
                :form="v$.form"
                :station-time-zone="stationTimeZone"
            />
            <form-advanced
                v-if="enableAdvancedFeatures"
                :form="v$.form"
            />
        </b-tabs>
    </modal-form>
</template>

<script>
import {required} from '@vuelidate/validators';
import FormBasicInfo from './Form/BasicInfo';
import FormSchedule from './Form/Schedule';
import FormAdvanced from './Form/Advanced';
import BaseEditModal from '~/components/Common/BaseEditModal';
import useVuelidate from "@vuelidate/core";

/* TODO Options API */

export default {
    name: 'EditModal',
    components: {FormSchedule, FormBasicInfo, FormAdvanced},
    mixins: [BaseEditModal],
    props: {
        stationTimeZone: {
            type: String,
            required: true
        },
        enableAdvancedFeatures: {
            type: Boolean,
            required: true
        }
    },
    emits: ['relist', 'needs-restart'],
    setup() {
        return {v$: useVuelidate()}
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
            'schedule_items': {}
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
        onSubmitSuccess() {
            this.$notifySuccess();

            this.$emit('needs-restart');
            this.$emit('relist');

            this.close();
        },
    }
};
</script>

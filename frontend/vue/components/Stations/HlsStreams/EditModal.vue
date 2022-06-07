<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">
        <b-tabs content-class="mt-3">
            <form-basic-info :form="$v.form"></form-basic-info>
        </b-tabs>
    </modal-form>
</template>
<script>
import {required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';
import FormBasicInfo from './Form/BasicInfo';
import mergeExisting from "~/functions/mergeExisting";

export default {
    name: 'EditModal',
    emits: ['needs-restart'],
    mixins: [BaseEditModal],
    components: {FormBasicInfo},
    validations() {
        return {
            form: {
                name: {required},
                format: {required},
                bitrate: {required}
            }
        };
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit HLS Stream')
                : this.$gettext('Add HLS Stream');
        }
    },
    methods: {
        resetForm() {
            this.form = {
                name: null,
                format: 'aac',
                bitrate: 128
            };
        },
        populateForm(d) {
            this.record = d;
            this.form = mergeExisting(this.form, d);
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

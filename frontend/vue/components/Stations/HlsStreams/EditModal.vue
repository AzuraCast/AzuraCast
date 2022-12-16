<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="v$.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">
        <b-tabs content-class="mt-3" pills>
            <form-basic-info :form="v$.form"></form-basic-info>
        </b-tabs>
    </modal-form>
</template>
<script>
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import FormBasicInfo from './Form/BasicInfo';
import mergeExisting from "~/functions/mergeExisting";
import useVuelidate from "@vuelidate/core";

export default {
    name: 'EditModal',
    emits: ['needs-restart'],
    setup() {
        return {v$: useVuelidate()}
    },
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
        onSubmitSuccess() {
            this.$notifySuccess();

            this.$emit('needs-restart');
            this.$emit('relist');

            this.close();
        },
    }
};
</script>

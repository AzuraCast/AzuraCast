<template>
    <modal-form
        ref="modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <b-tabs
            content-class="mt-3"
            pills
        >
            <form-basic-info :form="v$" />
        </b-tabs>
    </modal-form>
</template>

<script>
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import FormBasicInfo from './Form/BasicInfo';
import mergeExisting from "~/functions/mergeExisting";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";

/* TODO Options API */

export default {
    name: 'EditModal',
    components: {FormBasicInfo},
    mixins: [BaseEditModal],
    emits: ['relist', 'needs-restart'],
    setup() {
        const {form, resetForm, v$} = useVuelidateOnForm(
            {
                name: {required},
                format: {required},
                bitrate: {required}
            },
            {
                name: null,
                format: 'aac',
                bitrate: 128
            }
        );

        return {
            form,
            resetForm,
            v$
        }
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit HLS Stream')
                : this.$gettext('Add HLS Stream');
        }
    },
    methods: {
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

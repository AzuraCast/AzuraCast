<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="v$.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <admin-custom-fields-form :form="v$.form" :auto-assign-types="autoAssignTypes">
        </admin-custom-fields-form>

    </modal-form>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import AdminCustomFieldsForm from "~/components/Admin/CustomFields/Form";

export default {
    name: 'AdminCustomFieldsEditModal',
    components: {AdminCustomFieldsForm},
    setup() {
        return {v$: useVuelidate()}
    },
    mixins: [BaseEditModal],
    props: {
        autoAssignTypes: Object
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Custom Field')
                : this.$gettext('Add Custom Field');
        }
    },
    validations() {
        return {
            form: {
                'name': {required},
                'short_name': {},
                'auto_assign': {}
            }
        };
    },
    methods: {
        resetForm() {
            this.form = {
                'name': '',
                'short_name': '',
                'auto_assign': ''
            };
        }
    }
};
</script>

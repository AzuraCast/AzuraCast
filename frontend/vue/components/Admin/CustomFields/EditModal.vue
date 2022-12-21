<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="v$.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <admin-custom-fields-form :form="v$" :auto-assign-types="autoAssignTypes">
        </admin-custom-fields-form>

    </modal-form>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import AdminCustomFieldsForm from "~/components/Admin/CustomFields/Form";
import {ref} from "vue";

export default {
    name: 'AdminCustomFieldsEditModal',
    mixins: [BaseEditModal],
    components: {AdminCustomFieldsForm},
    setup() {
        const blankForm = {
            'name': '',
            'short_name': '',
            'auto_assign': ''
        };

        const form = ref(blankForm);

        const resetForm = () => {
            form.value = blankForm;
        }

        const validations = {
            'name': {required},
            'short_name': {},
            'auto_assign': {}
        };

        const v$ = useVuelidate(validations, form);

        return {
            form,
            resetForm,
            v$
        };
    },
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
};
</script>

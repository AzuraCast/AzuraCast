<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="v$.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <admin-custom-fields-form :form="v$" :auto-assign-types="autoAssignTypes">
        </admin-custom-fields-form>

    </modal-form>
</template>

<script>
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal.vue';
import AdminCustomFieldsForm from "~/components/Admin/CustomFields/Form.vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {defineComponent} from "vue";

export default defineComponent({
    name: 'AdminCustomFieldsEditModal',
    mixins: [BaseEditModal],
    components: {AdminCustomFieldsForm},
    setup() {
        const {form, resetForm, v$} = useVuelidateOnForm(
            {
                'name': {required},
                'short_name': {},
                'auto_assign': {}
            },
            {
                'name': '',
                'short_name': '',
                'auto_assign': ''
            }
        );

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
});
</script>

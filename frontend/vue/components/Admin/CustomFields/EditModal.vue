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
        <admin-custom-fields-form
            :form="v$"
            :auto-assign-types="autoAssignTypes"
        />
    </modal-form>
</template>

<script>
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal.vue';
import AdminCustomFieldsForm from "~/components/Admin/CustomFields/Form.vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {defineComponent} from "vue";

/* TODO Options API */

export default defineComponent({
    name: 'AdminCustomFieldsEditModal',
    components: {AdminCustomFieldsForm},
    mixins: [BaseEditModal],
    props: {
        autoAssignTypes: {
            type: Object,
            required: true
        }
    },
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
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Custom Field')
                : this.$gettext('Add Custom Field');
        }
    },
});
</script>

<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-overlay variant="card" :show="loading">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-form class="form" @submit.prevent="doSubmit">
                <b-tabs content-class="mt-3">
                    <admin-custom-fields-form :form="$v.form" :auto-assign-types="autoAssignTypes">
                    </admin-custom-fields-form>
                </b-tabs>

                <invisible-submit-button/>
            </b-form>
        </b-overlay>
        <template #modal-footer>
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
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import InvisibleSubmitButton from '~/components/Common/InvisibleSubmitButton';
import BaseEditModal from '~/components/Common/BaseEditModal';
import AdminCustomFieldsForm from "~/components/Admin/CustomFields/Form";

export default {
    name: 'AdminCustomFieldsEditModal',
    components: {AdminCustomFieldsForm, InvisibleSubmitButton},
    mixins: [validationMixin, BaseEditModal],
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

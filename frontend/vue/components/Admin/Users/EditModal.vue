<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error"
                :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <admin-users-form :form="$v.form" :roles="roles" :is-edit-mode="isEditMode"></admin-users-form>

    </modal-form>
</template>

<script>
import {validationMixin} from 'vuelidate';
import {email, required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';
import AdminUsersForm from './Form.vue';
import _ from 'lodash';
import validatePassword from "~/functions/validatePassword";

export default {
    name: 'AdminUsersEditModal',
    components: {AdminUsersForm},
    mixins: [validationMixin, BaseEditModal],
    props: {
        roles: Object
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit User')
                : this.$gettext('Add User');
        }
    },
    validations() {
        let validations = {
            form: {
                name: {},
                email: {required, email},
                roles: {}
            }
        };

        if (this.isEditMode) {
            validations.form.new_password = {validatePassword};
        } else {
            validations.form.new_password = {required, validatePassword};
        }

        return validations;
    },
    methods: {
        resetForm() {
            this.form = {
                name: '',
                email: '',
                new_password: '',
                roles: [],
            };
        },
        populateForm(data) {
            this.form = {
                name: data.name,
                email: data.email,
                new_password: '',
                roles: _.map(data.roles, 'id')
            };
        },
    }
};
</script>

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
        <admin-users-form
            :form="v$.form"
            :roles="roles"
            :is-edit-mode="isEditMode"
        />
    </modal-form>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import {email, required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import AdminUsersForm from './Form.vue';
import {map} from 'lodash';
import validatePassword from "~/functions/validatePassword";

/* TODO Options API */

export default {
    name: 'AdminUsersEditModal',
    components: {AdminUsersForm},
    mixins: [BaseEditModal],
    props: {
        roles: {
            type: Object,
            required: true
        }
    },
    setup() {
        return {v$: useVuelidate()}
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
                roles: map(data.roles, 'id')
            };
        },
    }
};
</script>

<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="v$.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <sftp-users-form :form="v$.form" :is-edit-mode="isEditMode"></sftp-users-form>

    </modal-form>
</template>
<script>
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import SftpUsersForm from "./Form";
import useVuelidate from "@vuelidate/core";

export default {
    name: 'SftpUsersEditModal',
    setup() {
        return {v$: useVuelidate()}
    },
    mixins: [BaseEditModal],
    components: {SftpUsersForm},
    validations() {
        return {
            form: {
                username: {required},
                password: this.isEditMode ? {} : {required},
                publicKeys: {}
            }
        };
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit SFTP User')
                : this.$gettext('Add SFTP User');
        }
    },
    methods: {
        resetForm() {
            this.form = {
                username: '',
                password: null,
                publicKeys: null
            };
        }
    }
};
</script>

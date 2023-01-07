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
        <storage-location-form :form="v$" />
    </modal-form>
</template>

<script>
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import StorageLocationForm from './Form';
import useVuelidate from "@vuelidate/core";
import {computed} from "vue";
import {useResettableRef} from "~/functions/useResettableRef";

/* TODO Options API */

export default {
    name: 'AdminStorageLocationsEditModal',
    components: {StorageLocationForm},
    mixins: [BaseEditModal],
    props: {
        type: {
            type: String,
            required: true
        }
    },
    setup() {
        const blankForm = {
            'adapter': 'local',
            'path': '',
            's3CredentialKey': null,
            's3CredentialSecret': null,
            's3Region': null,
            's3Version': 'latest',
            's3Bucket': null,
            's3Endpoint': null,
            'dropboxAuthToken': null,
            'sftpHost': null,
            'sftpPort': '22',
            'sftpUsername': null,
            'sftpPassword': null,
            'sftpPrivateKey': null,
            'sftpPrivateKeyPassPhrase': null,
            'storageQuota': ''
        }

        const {record: form, reset: resetForm} = useResettableRef(blankForm);

        const validations = computed(() => {
            let validationRules = {
                'adapter': {required},
                'storageQuota': {},
                'path': {},
                's3CredentialKey': {},
                's3CredentialSecret': {},
                's3Region': {},
                's3Version': {},
                's3Bucket': {},
                's3Endpoint': {},
                'dropboxAuthToken': {},
                'sftpHost': {},
                'sftpPort': {},
                'sftpUsername': {},
                'sftpPassword': {},
                'sftpPrivateKey': {},
                'sftpPrivateKeyPassPhrase': {}
            };

            switch (form.value.adapter) {
                case 'local':
                    validationRules.path = {required};
                    break;

                case 'dropbox':
                    validationRules.dropboxAuthToken = {required};
                    break;

                case 's3':
                    validationRules.s3CredentialKey = {required};
                    validationRules.s3CredentialSecret = {required};
                    validationRules.s3Region = {required};
                    validationRules.s3Version = {required};
                    validationRules.s3Bucket = {required};
                    validationRules.s3Endpoint = {required};
                    break;

                case 'sftp':
                    validationRules.sftpHost = {required};
                    validationRules.sftpPort = {required};
                    validationRules.sftpUsername = {required};
                    break;
            }

            return validationRules;
        });

        const v$ = useVuelidate(validations, form);

        return {
            form,
            resetForm,
            v$
        };
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Storage Location')
                : this.$gettext('Add Storage Location');
        }
    },
    methods: {
        getSubmittableFormData() {
            if (this.isEditMode) {
                return this.form;
            }

            return {
                ...this.form,
                type: this.type
            };
        }
    }
};
</script>

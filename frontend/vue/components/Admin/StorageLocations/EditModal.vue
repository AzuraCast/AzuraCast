<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <storage-location-form :form="$v.form"></storage-location-form>

    </modal-form>
</template>

<script>
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';
import StorageLocationForm from './Form';

export default {
    name: 'AdminStorageLocationsEditModal',
    components: {StorageLocationForm},
    mixins: [validationMixin, BaseEditModal],
    props: {
        type: String
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Storage Location')
                : this.$gettext('Add Storage Location');
        }
    },
    validations() {
        let validations = {
            form: {
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
            }
        };

        switch (this.form.adapter) {
            case 'local':
                validations.form.path = {required};
                break;

            case 'dropbox':
                validations.form.dropboxAuthToken = {required};
                break;

            case 's3':
                validations.form.s3CredentialKey = { required };
                validations.form.s3CredentialSecret = { required };
                validations.form.s3Region = { required };
                validations.form.s3Version = { required };
                validations.form.s3Bucket = { required };
                validations.form.s3Endpoint = { required };
                break;
                
            case 'sftp':
                validations.form.sftpHost = { required };
                validations.form.sftpPort = { required };
                validations.form.sftpUsername = { required };
                break;
        }

        return validations;
    },
    methods: {
        resetForm() {
            this.form = {
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
            };
        },
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

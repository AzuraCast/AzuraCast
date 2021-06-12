<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-overlay variant="card" :show="loading">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-form class="form" @submit.prevent="doSubmit">
                <storage-location-form :form="$v.form"></storage-location-form>
                <invisible-submit-button/>
            </b-form>
        </b-overlay>
        <template v-slot:modal-footer>
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
import axios from 'axios';
import { validationMixin } from 'vuelidate';
import required from 'vuelidate/src/validators/required';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import BaseEditModal from '../../Common/BaseEditModal';
import StorageLocationForm from './Form';

export default {
    name: 'AdminStorageLocationsEditModal',
    components: { StorageLocationForm, InvisibleSubmitButton },
    mixins: [validationMixin, BaseEditModal],
    props: {
        type: String
    },
    computed: {
        langTitle () {
            return this.isEditMode
                ? this.$gettext('Edit Storage Location')
                : this.$gettext('Add Storage Location');
        }
    },
    validations () {
        let validations = {
            form: {
                'adapter': { required },
                'storageQuota': {}
            }
        };

        switch (this.form.adapter) {
            case 'local':
                validations.form.path = { required };
                validations.form.s3CredentialKey = {};
                validations.form.s3CredentialSecret = {};
                validations.form.s3Region = {};
                validations.form.s3Version = {};
                validations.form.s3Bucket = {};
                validations.form.s3Endpoint = {};
                validations.form.dropboxAuthToken = {};
                break;

            case 'dropbox':
                validations.form.path = {};
                validations.form.s3CredentialKey = {};
                validations.form.s3CredentialSecret = {};
                validations.form.s3Region = {};
                validations.form.s3Version = {};
                validations.form.s3Bucket = {};
                validations.form.s3Endpoint = {};
                validations.form.dropboxAuthToken = { required };
                break;

            case 's3':
                validations.form.path = {};
                validations.form.s3CredentialKey = { required };
                validations.form.s3CredentialSecret = { required };
                validations.form.s3Region = { required };
                validations.form.s3Version = { required };
                validations.form.s3Bucket = { required };
                validations.form.s3Endpoint = { required };
                validations.form.dropboxAuthToken = {};
                break;
        }

        return validations;
    },
    methods: {
        resetForm () {
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
                'storageQuota': ''
            };
        },
        populateForm (d) {
            this.form = {
                'adapter': d.adapter,
                'path': d.path,
                's3CredentialKey': d.s3CredentialKey,
                's3CredentialSecret': d.s3CredentialSecret,
                's3Region': d.s3Region,
                's3Version': d.s3Version,
                's3Bucket': d.s3Bucket,
                's3Endpoint': d.s3Endpoint,
                'dropboxAuthToken': d.dropboxAuthToken,
                'storageQuota': d.storageQuota
            };
        },
        doSubmit () {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.error = null;

            let data = this.form;
            data.type = this.type;

            axios({
                method: (this.isEditMode)
                    ? 'PUT'
                    : 'POST',
                url: (this.isEditMode)
                    ? this.editUrl
                    : this.createUrl,
                data: data
            }).then((resp) => {
                let notifyMessage = this.$gettext('Changes saved.');
                notify('<b>' + notifyMessage + '</b>', 'success');

                this.$emit('relist');
                this.close();
            }).catch((error) => {
                let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');

                if (error.response) {
                    // Request made and server responded
                    notifyMessage = error.response.data.message;
                    console.log(notifyMessage);
                } else if (error.request) {
                    // The request was made but no response was received
                    console.log(error.request);
                } else {
                    // Something happened in setting up the request that triggered an Error
                    console.log('Error', error.message);
                }

                this.error = notifyMessage;
            });
        }
    }
};
</script>

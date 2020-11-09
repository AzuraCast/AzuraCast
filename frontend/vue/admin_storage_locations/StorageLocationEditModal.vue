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
import InvisibleSubmitButton from '../components/InvisibleSubmitButton';
import StorageLocationForm from './form/StorageLocationForm';

export default {
    name: 'EditModal',
    components: { StorageLocationForm, InvisibleSubmitButton },
    mixins: [validationMixin],
    props: {
        createUrl: String,
        type: String
    },
    data () {
        return {
            loading: true,
            editUrl: null,
            error: null,
            form: {}
        };
    },
    computed: {
        langTitle () {
            return this.isEditMode
                ? this.$gettext('Edit Storage Location')
                : this.$gettext('Add Storage Location');
        },
        isEditMode () {
            return this.editUrl !== null;
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
                break;

            case 's3':
                validations.form.path = {};
                validations.form.s3CredentialKey = { required };
                validations.form.s3CredentialSecret = { required };
                validations.form.s3Region = { required };
                validations.form.s3Version = { required };
                validations.form.s3Bucket = { required };
                validations.form.s3Endpoint = { required };
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
                'storageQuota': ''
            };
        },
        create () {
            this.resetForm();
            this.loading = false;
            this.editUrl = null;
            this.error = null;

            this.$refs.modal.show();
        },
        edit (recordUrl) {
            this.resetForm();
            this.loading = true;
            this.editUrl = recordUrl;
            this.error = null;

            this.$refs.modal.show();

            axios.get(this.editUrl).then((resp) => {
                let d = resp.data;

                this.form = {
                    'adapter': d.adapter,
                    'path': d.path,
                    's3CredentialKey': d.s3CredentialKey,
                    's3CredentialSecret': d.s3CredentialSecret,
                    's3Region': d.s3Region,
                    's3Version': d.s3Version,
                    's3Bucket': d.s3Bucket,
                    's3Endpoint': d.s3Endpoint,
                    'storageQuota': d.storageQuota
                };

                this.loading = false;
            }).catch((err) => {
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

                notify('<b>' + notifyMessage + '</b>', 'danger', false);

                this.$emit('relist');
                this.close();
            });
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
                notify('<b>' + notifyMessage + '</b>', 'success', false);

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
        },
        close () {
            this.loading = false;
            this.editUrl = null;
            this.error = null;
            this.resetForm();

            this.$v.form.$reset();
            this.$refs.modal.hide();
        }
    }
};
</script>

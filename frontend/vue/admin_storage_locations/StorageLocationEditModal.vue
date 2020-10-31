<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-overlay variant="card" :show="loading">
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
    validations: {
        form: {
            'adapter': { required },
            'path': {},
            's3CredentialKey': {},
            's3CredentialSecret': {},
            's3Region': {},
            's3Version': {},
            's3Bucket': {},
            's3Endpoint': {},
            'storageQuota': {}
        }
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

            this.$refs.modal.show();
        },
        edit (recordUrl) {
            this.resetForm();
            this.loading = true;
            this.editUrl = recordUrl;
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
                console.log(err);
                this.close();
            });
        },
        doSubmit () {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

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
            }).catch((err) => {
                console.error(err);

                let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');
                notify('<b>' + notifyMessage + '</b>', 'danger', false);

                this.$emit('relist');
                this.close();
            });
        },
        close () {
            this.loading = false;
            this.editUrl = null;
            this.resetForm();

            this.$v.form.$reset();
            this.$refs.modal.hide();
        }
    }
};
</script>

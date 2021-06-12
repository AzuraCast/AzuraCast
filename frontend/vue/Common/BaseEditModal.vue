<template>
    <b-modal ref="modal"></b-modal>
</template>

<script>
import axios from 'axios';
import { validationMixin } from 'vuelidate';

export default {
    name: 'BaseEditModal',
    emits: ['relist'],
    props: {
        createUrl: String
    },
    mixins: [
        validationMixin
    ],
    data () {
        return {
            loading: true,
            error: null,
            editUrl: null,
            form: {}
        };
    },
    computed: {
        isEditMode () {
            return this.editUrl !== null;
        }
    },
    methods: {
        resetForm () {
            this.form = {};
        },
        create () {
            this.resetForm();
            this.loading = false;
            this.error = null;
            this.editUrl = null;

            this.$refs.modal.show();
        },
        edit (recordUrl) {
            this.resetForm();
            this.loading = true;
            this.error = null;
            this.editUrl = recordUrl;
            this.$refs.modal.show();

            this.doLoad(recordUrl);
        },
        doLoad (recordUrl) {
            axios.get(recordUrl).then((resp) => {
                this.populateForm(resp.data);
                this.loading = false;
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

                notify('<b>' + notifyMessage + '</b>', 'danger');
                this.close();
            });
        },
        populateForm (data) {
            this.form = data;
        },
        doSubmit () {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.error = null;

            axios({
                method: (this.isEditMode)
                    ? 'PUT'
                    : 'POST',
                url: (this.isEditMode)
                    ? this.editUrl
                    : this.createUrl,
                data: this.form
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
        },
        close () {
            this.loading = false;
            this.error = null;
            this.editUrl = null;
            this.resetForm();

            this.$v.form.$reset();
            this.$refs.modal.hide();
        }
    }
};
</script>

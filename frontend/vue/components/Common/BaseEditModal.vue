<template>
    <b-modal ref="modal" />
</template>

<script>
import useVuelidate from "@vuelidate/core";
import ModalForm from "~/components/Common/ModalForm";
import mergeExisting from "~/functions/mergeExisting";

/* TODO Options API */

export default {
    name: 'BaseEditModal',
    components: {ModalForm}, // eslint-disable-line
    props: {
        createUrl: {
            type: String,
            required: true
        }
    },
    emits: ['relist'],
    setup() {
        return {v$: useVuelidate()}
    },
    data() {
        return {
            loading: true,
            error: null,
            editUrl: null,
            form: {}
        };
    },
    computed: {
        langTitle () {
            return this.isEditMode
                ? this.$gettext('Edit Record')
                : this.$gettext('Add Record');
        },
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
            this.$wrapWithLoading(
                this.axios.get(recordUrl)
            ).then((resp) => {
                this.populateForm(resp.data);
                this.loading = false;
            }).catch(() => {
                this.close();
            });
        },
        populateForm(data) {
            this.form = mergeExisting(this.form, data);
        },
        getSubmittableFormData() {
            return this.form;
        },
        buildSubmitRequest() {
            return {
                method: (this.isEditMode)
                    ? 'PUT'
                    : 'POST',
                url: (this.isEditMode)
                    ? this.editUrl
                    : this.createUrl,
                data: this.getSubmittableFormData()
            };
        },
        onSubmitSuccess() {
            this.$notifySuccess();
            this.$emit('relist');
            this.close();
        },
        onSubmitError(error) {
            this.error = error.response.data.message;
        },
        async doSubmit() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
                return;
            }

            this.error = null;

            this.$wrapWithLoading(
                this.axios(this.buildSubmitRequest())
            ).then((resp) => {
                this.onSubmitSuccess(resp);
            }).catch((error) => {
                this.onSubmitError(error);
            });
        },
        close() {
            this.$refs.modal.hide();
        },
        clearContents() {
            this.v$.$reset();

            this.loading = false;
            this.error = null;
            this.editUrl = null;
            this.resetForm();
        },
    }
};
</script>

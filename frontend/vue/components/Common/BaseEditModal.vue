<template>
    <b-modal ref="modal"></b-modal>
</template>

<script>
import {validationMixin} from 'vuelidate';
import ModalForm from "~/components/Common/ModalForm";
import mergeExisting from "~/functions/mergeExisting";

export default {
    name: 'BaseEditModal',
    components: {ModalForm},
    emits: ['relist'],
    props: {
        createUrl: String
    },
    mixins: [
        validationMixin
    ],
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
            }).catch((error) => {
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
        onSubmitSuccess(response) {
            this.$notifySuccess();
            this.$emit('relist');
            this.close();
        },
        onSubmitError(error) {
            this.error = error.response.data.message;
        },
        doSubmit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
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
            this.$v.form.$reset();

            this.loading = false;
            this.error = null;
            this.editUrl = null;
            this.resetForm();
        },
    }
};
</script>

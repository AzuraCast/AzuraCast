<template>
    <b-overlay variant="card" :show="loading">
        <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

        <b-form class="form vue-form" @submit.prevent="submit">
            <b-tabs card lazy justified>


            </b-tabs>

            <slot name="submitButton">
                <b-card-body body-class="card-padding-sm">
                    <b-button size="lg" type="submit" variant="primary" :disabled="!isValid">
                        <translate key="lang_btn_save_changes">Save Changes</translate>
                    </b-button>
                </b-card-body>
            </slot>
        </b-form>
    </b-overlay>
</template>

<script>
import {validationMixin} from "vuelidate";

export default {
    name: 'AdminStationsForm',
    emits: ['error', 'submitted'],
    props: {
        createUrl: String,
        editUrl: String,
        isEditMode: Boolean,
        showAdminTab: {
            type: Boolean,
            default: true
        }
    },
    mixins: [
        validationMixin
    ],
    validations: {
        form: {}
    },
    data() {
        return {
            loading: true,
            error: null,
            form: {}
        };
    },
    computed: {
        isValid() {
            return !this.$v.form.$invalid;
        }
    },
    methods: {
        clear() {
            this.loading = false;
            this.error = null;
            this.form = {};
        },
        reset() {
            this.clear();
            if (this.isEditMode) {
                this.doLoad();
            }
        },

        doLoad() {
            this.$wrapWithLoading(
                this.axios.get(this.editUrl)
            ).then((resp) => {
                this.populateForm(resp.data);
            }).catch((error) => {
                this.$emit('error', error);
            }).finally(() => {
                this.loading = false;
            });
        },
        populateForm(data) {
            this.form = data;
        },
        getSubmittableFormData() {
            return this.form;
        },
        submit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.error = null;

            this.$wrapWithLoading(
                this.axios({
                    method: (this.isEditMode)
                        ? 'PUT'
                        : 'POST',
                    url: (this.isEditMode)
                        ? this.editUrl
                        : this.createUrl,
                    data: this.getSubmittableFormData()
                })
            ).then((resp) => {
                this.$notifySuccess();
                this.$emit('submitted');
            }).catch((error) => {
                this.error = error.response.data.message;
            });
        },
    }
}
</script>

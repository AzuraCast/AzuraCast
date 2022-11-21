<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <account-edit-form :form="$v.form" :supported-locales="supportedLocales"></account-edit-form>

    </modal-form>

</template>

<script>
import ModalForm from "~/components/Common/ModalForm";
import {validationMixin} from "vuelidate";
import {email, required} from 'vuelidate/dist/validators.min.js';
import AccountEditForm from "./EditForm";
import mergeExisting from "~/functions/mergeExisting";

export default {
    name: 'AccountEditModal',
    components: {AccountEditForm, ModalForm,},
    mixins: [validationMixin],
    emits: ['relist'],
    props: {
        userUrl: String,
        supportedLocales: Object
    },
    data() {
        return {
            loading: true,
            error: null,
            form: {}
        };
    },
    validations() {
        return {
            form: {
                name: {},
                email: {required, email},
                locale: {required},
                theme: {required},
                show_24_hour_time: {}
            }
        };
    },
    computed: {
        langTitle() {
            return this.$gettext('Edit Profile');
        }
    },
    methods: {
        resetForm() {
            this.form = {
                name: '',
                email: '',
                locale: 'default',
                theme: 'browser',
                show_24_hour_time: null,
            };
        },
        open() {
            this.resetForm();
            this.loading = false;
            this.error = null;

            this.$refs.modal.show();

            this.$wrapWithLoading(
                this.axios.get(this.userUrl)
            ).then((resp) => {
                this.form = mergeExisting(this.form, resp.data);
                this.loading = false;
            }).catch((error) => {
                this.close();
            });
        },
        doSubmit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.error = null;

            this.$wrapWithLoading(
                this.axios({
                    method: 'PUT',
                    url: this.userUrl,
                    data: this.form
                })
            ).then((resp) => {
                this.$notifySuccess();
                this.$emit('relist');
                this.close();
            }).catch((error) => {
                this.error = error.response.data.message;
            });
        },
        close() {
            this.$refs.modal.hide();
        },
        clearContents() {
            this.$v.form.$reset();

            this.loading = false;
            this.error = null;

            this.resetForm();
        },
    }
}
</script>

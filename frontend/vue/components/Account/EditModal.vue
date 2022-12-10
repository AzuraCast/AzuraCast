<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="v$.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <account-edit-form :form="v$.form" :supported-locales="supportedLocales"></account-edit-form>

    </modal-form>
</template>

<script>
import mergeExisting from "~/functions/mergeExisting";
import {email, required} from '@vuelidate/validators';
import useVuelidate from "@vuelidate/core";
import AccountEditForm from "./EditForm.vue";
import ModalForm from "~/components/Common/ModalForm.vue";

export default {
    name: 'AccountEditModal',
    components: {ModalForm, AccountEditForm},
    emits: ['relist'],
    props: {
        userUrl: String,
        supportedLocales: Object
    },
    setup() {
        return {
            v$: useVuelidate()
        };
    },
    data() {
        return {
            loading: true,
            error: null,
            form: {
                ...this.getBlankForm(),
            }
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
        getBlankForm() {
            return {
                name: '',
                email: '',
                locale: 'default',
                theme: 'browser',
                show_24_hour_time: null,
            };
        },
        resetForm() {
            this.form = {
                ...this.getBlankForm()
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
            }).catch(() => {
                this.close();
            });
        },
        doSubmit() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
                return;
            }

            this.error = null;

            this.$wrapWithLoading(
                this.axios({
                    method: 'PUT',
                    url: this.userUrl,
                    data: this.form
                })
            ).then(() => {
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
            this.v$.$reset();

            this.loading = false;
            this.error = null;

            this.resetForm();
        },
    }
}
</script>

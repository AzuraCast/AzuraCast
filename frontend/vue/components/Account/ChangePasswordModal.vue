<template>
    <modal-form ref="modal" size="md" centered :title="langTitle" :disable-save-button="v$.$invalid"
                @submit="onSubmit" @hidden="onHidden">
        <b-form-fieldset>
            <b-wrapped-form-group id="form_current_password" :field="v$.form.current_password"
                                  input-type="password" autofocus>
                <template #label>
                    {{ $gettext('Current Password') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group id="form_new_password" :field="v$.form.new_password" input-type="password">
                <template #label>
                    {{ $gettext('New Password') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group id="form_current_password" :field="v$.form.new_password2" input-type="password">
                <template #label>
                    {{ $gettext('Confirm New Password') }}
                </template>
            </b-wrapped-form-group>
        </b-form-fieldset>

        <template #save-button-name>
            {{ langTitle }}
        </template>
    </modal-form>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import ModalForm from "~/components/Common/ModalForm";
import BFormFieldset from "~/components/Form/BFormFieldset";
import {required, sameAs} from "@vuelidate/validators";
import validatePassword from "~/functions/validatePassword";
import useVuelidate from "@vuelidate/core";

export default {
    components: {BWrappedFormGroup, ModalForm, BFormFieldset},
    props: {
        changePasswordUrl: String
    },
    emits: ['relist'],
    setup() {
        return {
            v$: useVuelidate()
        };
    },
    data() {
        return {
            form: this.getBlankForm(),
        };
    },
    validations: {
        form: {
            current_password: {required},
            new_password: {required, validatePassword},
            new_password2: {
                required,
                sameAs: sameAs('new_password')
            }
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Change Password');
        }
    },
    methods: {
        getBlankForm() {
            return {
                current_password: null,
                new_password: null,
                new_password2: null
            };
        },
        open() {
            this.resetForm();
            this.$refs.modal.show();
        },
        close() {
            this.$refs.modal.hide();
        },
        onHidden() {
            this.clearContents();
        },
        resetForm() {
            this.form = this.getBlankForm();
        },
        onSubmit() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
                return;
            }

            this.$wrapWithLoading(
                this.axios.put(this.changePasswordUrl, this.form)
            ).finally(() => {
                this.$refs.modal.hide();
                this.$emit('relist');
            });
        },
        clearContents() {
            this.v$.$reset();

            this.error = null;
            this.resetForm();
        },
    }
};
</script>

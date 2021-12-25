<template>
    <modal-form ref="modal" size="md" centered :title="langTitle" :disable-save-button="$v.form.$invalid"
                @submit="onSubmit" @hidden="onHidden">
        <b-form-fieldset>
            <b-wrapped-form-group id="form_current_password" :field="$v.form.current_password"
                                  input-type="password" autofocus>
                <template #label="{lang}">
                    <translate :key="lang">Current Password</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group id="form_new_password" :field="$v.form.new_password" input-type="password">
                <template #label="{lang}">
                    <translate :key="lang">New Password</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group id="form_current_password" :field="$v.form.new_password2" input-type="password">
                <template #label="{lang}">
                    <translate :key="lang">Confirm New Password</translate>
                </template>
            </b-wrapped-form-group>
        </b-form-fieldset>

        <template #save-button-name>
            {{ langTitle }}
        </template>
    </modal-form>
</template>
<script>
import {validationMixin} from 'vuelidate';
import {required, sameAs} from 'vuelidate/dist/validators.min.js';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import ModalForm from "~/components/Common/ModalForm";
import BFormFieldset from "~/components/Form/BFormFieldset";
import validatePassword from "~/functions/validatePassword";

export default {
    name: 'AccountChangePasswordModal',
    components: {ModalForm, BWrappedFormGroup, BFormFieldset},
    emits: ['relist'],
    mixins: [validationMixin],
    props: {
        changePasswordUrl: String
    },
    data() {
        return {
            form: {
                current_password: null,
                new_password: null,
                new_password2: null
            }
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
        open() {
            this.$refs.modal.show();
        },
        close() {
            this.$refs.modal.hide();
        },
        onHidden() {
            this.$v.form.$reset();
        },
        onSubmit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.$wrapWithLoading(
                this.axios.put(this.changePasswordUrl, this.form)
            ).finally(() => {
                this.$refs.modal.hide();
                this.$emit('relist');
            });
        }
    }
};
</script>

<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents" no-enforce-focus>

        <b-row>
            <b-col md="7">
                <h5 class="mt-2">
                    <translate key="lang_2fa_hdr_1">Step 1: Scan QR Code</translate>
                </h5>

                <p class="card-text">
                    <translate key="lang_2fa_1">From your smartphone, scan the code to the right using an authentication app of your choice (FreeOTP, Authy, etc).</translate>
                </p>

                <h5 class="mt-0">
                    <translate key="lang_2fa_hdr_2">Step 2: Verify Generated Code</translate>
                </h5>

                <p class="card-text">
                    <translate key="lang_2fa_2">To verify that the code was set up correctly, enter the 6-digit code the app shows you.</translate>
                </p>

                <b-form-fieldset>
                    <b-wrapped-form-group id="form_otp" :field="$v.form.otp" autofocus>
                        <template #label="{lang}">
                            <translate :key="lang">Code from Authenticator App</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">Enter the current code provided by your authenticator app to verify that it's working correctly.</translate>
                        </template>
                    </b-wrapped-form-group>
                </b-form-fieldset>
            </b-col>
            <b-col md="5">
                <b-img :src="totp.qr_code"></b-img>

                <div v-if="totp.totp_uri" class="mt-2">
                    <code id="totp_uri" class="d-inline-block text-truncate" style="width: 100%;">
                        {{ totp.totp_uri }}
                    </code>
                    <copy-to-clipboard-button :text="totp.totp_uri"></copy-to-clipboard-button>
                </div>
            </b-col>
        </b-row>

        <template #save-button-name>
            <translate key="lang_btn_submit">Submit Code</translate>
        </template>
    </modal-form>

</template>

<script>
import ModalForm from "~/components/Common/ModalForm";
import {validationMixin} from "vuelidate";
import {minLength, required} from 'vuelidate/dist/validators.min.js';
import CopyToClipboardButton from "~/components/Common/CopyToClipboardButton";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'AccountTwoFactorModal',
    components: {ModalForm, CopyToClipboardButton, BFormFieldset, BWrappedFormGroup},
    mixins: [validationMixin],
    emits: ['relist'],
    props: {
        twoFactorUrl: String
    },
    data() {
        return {
            loading: true,
            error: null,
            totp: {
                secret: null,
                totp_uri: null,
                qr_code: null
            },
            form: {
                otp: null
            }
        };
    },
    validations() {
        return {
            form: {
                otp: {
                    required,
                    minLength: minLength(6)
                }
            }
        };
    },
    computed: {
        langTitle() {
            return this.$gettext('Enable Two-Factor Authentication');
        }
    },
    methods: {
        resetForm() {
            this.totp = {
                secret: null,
                totp_uri: null,
                qr_code: null
            };
            this.form = {
                otp: '',
            };
        },
        open() {
            this.resetForm();
            this.loading = false;
            this.error = null;

            this.$refs.modal.show();

            this.$wrapWithLoading(
                this.axios.put(this.twoFactorUrl)
            ).then((resp) => {
                this.totp = resp.data;
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
                    url: this.twoFactorUrl,
                    data: {
                        secret: this.totp.secret,
                        otp: this.form.otp
                    }
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

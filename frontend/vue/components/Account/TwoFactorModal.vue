<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Enable Two-Factor Authentication')"
        :error="error"
        :disable-save-button="v$.$invalid"
        no-enforce-focus
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <b-row>
            <b-col md="7">
                <h5 class="mt-2">
                    {{ $gettext('Step 1: Scan QR Code') }}
                </h5>

                <p class="card-text">
                    {{
                        $gettext('From your smartphone, scan the code to the right using an authentication app of your choice (FreeOTP, Authy, etc).')
                    }}
                </p>

                <h5 class="mt-0">
                    {{ $gettext('Step 2: Verify Generated Code') }}
                </h5>

                <p class="card-text">
                    {{
                        $gettext('To verify that the code was set up correctly, enter the 6-digit code the app shows you.')
                    }}
                </p>

                <b-form-fieldset>
                    <b-wrapped-form-group
                        id="form_otp"
                        :field="v$.otp"
                        autofocus
                    >
                        <template #label>
                            {{ $gettext('Code from Authenticator App') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('Enter the current code provided by your authenticator app to verify that it\'s working correctly.')
                            }}
                        </template>
                    </b-wrapped-form-group>
                </b-form-fieldset>
            </b-col>
            <b-col md="5">
                <b-img :src="totp.qr_code" />

                <div
                    v-if="totp.totp_uri"
                    class="mt-2"
                >
                    <code
                        id="totp_uri"
                        class="d-inline-block text-truncate"
                        style="width: 100%;"
                    >
                        {{ totp.totp_uri }}
                    </code>
                    <copy-to-clipboard-button :text="totp.totp_uri" />
                </div>
            </b-col>
        </b-row>

        <template #save-button-name>
            {{ $gettext('Submit Code') }}
        </template>
    </modal-form>
</template>

<script setup>
import ModalForm from "~/components/Common/ModalForm";
import CopyToClipboardButton from "~/components/Common/CopyToClipboardButton";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {minLength, required} from "@vuelidate/validators";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useResettableRef} from "~/functions/useResettableRef";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    twoFactorUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const loading = ref(true);
const error = ref(null);

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        otp: {
            required,
            minLength: minLength(6)
        }
    },
    {
        otp: ''
    }
);

const {record: totp, reset: resetTotp} = useResettableRef({
    secret: null,
    totp_uri: null,
    qr_code: null
});

const clearContents = () => {
    resetForm();
    resetTotp();

    loading.value = false;
    error.value = null;
};

const $modal = ref(); // BModal

const close = () => {
    $modal.value?.hide();
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const open = () => {
    clearContents();

    loading.value = true;

    $modal.value?.show();

    wrapWithLoading(
        axios.put(props.twoFactorUrl)
    ).then((resp) => {
        totp.value = resp.data;
        loading.value = false;
    }).catch(() => {
        close();
    });
};

const doSubmit = () => {
    ifValid(() => {
        error.value = null;

        wrapWithLoading(
            axios({
                method: 'PUT',
                url: props.twoFactorUrl,
                data: {
                    secret: totp.value.secret,
                    otp: form.value.otp
                }
            })
        ).then(() => {
            notifySuccess();
            emit('relist');
            close();
        }).catch((error) => {
            error.value = error.response.data.message;
        });
    });
};

defineExpose({
    open
});
</script>

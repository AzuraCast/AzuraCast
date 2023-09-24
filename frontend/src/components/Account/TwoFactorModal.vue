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
        <div class="row">
            <div class="col-md-7">
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

                <form-fieldset>
                    <form-group-field
                        id="form_otp"
                        :field="v$.otp"
                        autofocus
                        :label="$gettext('Code from Authenticator App')"
                        :description="$gettext('Enter the current code provided by your authenticator app to verify that it\'s working correctly.')"
                    />
                </form-fieldset>
            </div>
            <div class="col-md-5">
                <img
                    :src="totp.qr_code"
                    :alt="$gettext('QR Code')"
                >

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
            </div>
        </div>

        <template #save-button-name>
            {{ $gettext('Submit Code') }}
        </template>
    </modal-form>
</template>

<script setup lang="ts">
import ModalForm from "~/components/Common/ModalForm.vue";
import CopyToClipboardButton from "~/components/Common/CopyToClipboardButton.vue";
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {minLength, required} from "@vuelidate/validators";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useResettableRef} from "~/functions/useResettableRef";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {ModalFormTemplateRef} from "~/functions/useBaseEditModal.ts";

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

const $modal = ref<ModalFormTemplateRef>(null);

const close = () => {
    $modal.value?.hide();
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const open = () => {
    clearContents();

    loading.value = true;

    $modal.value?.show();

    axios.put(props.twoFactorUrl).then((resp) => {
        totp.value = resp.data;
        loading.value = false;
    }).catch(() => {
        close();
    });
};

const doSubmit = () => {
    ifValid(() => {
        error.value = null;

        axios({
            method: 'PUT',
            url: props.twoFactorUrl,
            data: {
                secret: totp.value.secret,
                otp: form.value.otp
            }
        }).then(() => {
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

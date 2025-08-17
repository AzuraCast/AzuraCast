<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Enable Two-Factor Authentication')"
        :error="error"
        :disable-save-button="r$.$invalid"
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
                        :field="r$.otp"
                        autofocus
                        :label="$gettext('Code from Authenticator App')"
                        :description="$gettext('Enter the current code provided by your authenticator app to verify that it\'s working correctly.')"
                    />
                </form-fieldset>
            </div>
            <div class="col-md-5">
                <div
                    v-if="totp.totp_uri"
                >
                    <qr-code :uri="totp.totp_uri" />

                    <code
                        id="totp_uri"
                        class="d-inline-block text-truncate mt-2"
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
import {minLength, required} from "@regle/rules";
import {ref, useTemplateRef} from "vue";
import {useResettableRef} from "~/functions/useResettableRef";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import QrCode from "~/components/Account/QrCode.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useAppRegle} from "~/vendor/regle.ts";

const props = defineProps<{
    twoFactorUrl: string,
}>();

const emit = defineEmits<HasRelistEmit>();

const loading = ref(true);
const error = ref(null);

const {record: form, reset: resetForm} = useResettableRef({
    otp: ''
});

const {r$} = useAppRegle(
    form,
    {
        otp: {
            required,
            minLength: minLength(6)
        }
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

const $modal = useTemplateRef('$modal');
const {hide, show} = useHasModal($modal);

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const open = () => {
    clearContents();

    loading.value = true;

    show();

    axios.put(props.twoFactorUrl).then((resp) => {
        totp.value = resp.data;
        loading.value = false;
    }).catch(() => {
        hide();
    });
};

const doSubmit = async () => {
    const {valid} = await r$.$validate();
    if (!valid) {
        return;
    }

    error.value = null;

    try {
        await axios({
            method: 'PUT',
            url: props.twoFactorUrl,
            data: {
                secret: totp.value.secret,
                otp: form.value.otp
            }
        });

        notifySuccess();
        emit('relist');
        hide();
    } catch (e) {
        error.value = e.response.data.message;
    }
};

defineExpose({
    open
});
</script>

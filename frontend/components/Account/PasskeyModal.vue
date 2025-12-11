<template>
    <modal
        id="api_keys_modal"
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Add New Passkey')"
        no-enforce-focus
        @hidden="onHidden"
    >
        <template #default>
            <div
                v-show="error != null"
                class="alert alert-danger"
            >
                {{ error }}
            </div>

            <form
                v-if="isSupported"
                class="form vue-form"
                @submit.prevent="doSubmit"
            >
                <form-group-field
                    id="form_name"
                    :field="r$.name"
                    autofocus
                    class="mb-3"
                    :label="$gettext('Passkey Nickname')"
                />

                <form-markup id="form_select_passkey">
                    <template #label>
                        {{ $gettext('Select Passkey') }}
                    </template>

                    <p class="card-text">
                        {{ $gettext('Click the button below to open your browser window to select a passkey.') }}
                    </p>

                    <p
                        v-if="form.createResponse"
                        class="card-text"
                    >
                        {{ $gettext('A passkey has been selected. Submit this form to add it to your account.') }}
                    </p>
                    <div
                        v-else
                        class="buttons"
                    >
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="selectPasskey"
                        >
                            {{ $gettext('Select Passkey') }}
                        </button>
                    </div>
                </form-markup>

                <invisible-submit-button />
            </form>

            <div v-else>
                <p class="card-text">
                    {{
                        $gettext('Your browser does not support passkeys. Consider updating your browser to the latest version.')
                    }}
                </p>
            </div>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="hide"
                >
                    {{ $gettext('Close') }}
                </button>
                <button
                    type="submit"
                    class="btn"
                    :class="(r$.$invalid) ? 'btn-danger' : 'btn-primary'"
                    @click="doSubmit"
                >
                    {{ $gettext('Add New Passkey') }}
                </button>
            </slot>
        </template>
    </modal>
</template>

<script setup lang="ts">
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {required} from "@regle/rules";
import {ref, useTemplateRef} from "vue";
import {getErrorAsString, useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import useWebAuthn from "~/functions/useWebAuthn.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import {isObject} from "@vueuse/core";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const emit = defineEmits<HasRelistEmit>();

const {getApiUrl} = useApiRouter();
const registerWebAuthnUrl = getApiUrl('/frontend/account/webauthn/register');

const error = ref<string | null>(null);

type PasskeyRow = {
    name: string,
    createResponse: string | null
}

const form = ref<PasskeyRow>({
    name: '',
    createResponse: null
});

const {r$} = useAppRegle(
    form,
    {
        name: {required},
        createResponse: {required}
    },
    {}
);

const clearContents = () => {
    r$.$reset({
        toOriginalState: true
    });

    error.value = null;
};

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const create = () => {
    clearContents();
    show();
};

const {isSupported, doRegister, cancel} = useWebAuthn();

const onHidden = () => {
    clearContents();
    cancel();
    emit('relist');
};

const {axios} = useAxios();

const selectPasskey = async () => {
    const {data: registerArgs} = await axios.get(registerWebAuthnUrl.value);

    try {
        const createResponse = await doRegister(registerArgs);
        r$.$value.createResponse = JSON.stringify(createResponse);
    } catch (err) {
        if (isObject(err) && 'name' in err && err.name === 'InvalidStateError') {
            error.value = 'Error: Authenticator was probably already registered by user';
        } else {
            error.value = String(err);
        }

        throw err;
    }
};

const doSubmit = async () => {
    const {valid, data} = await r$.$validate();
    if (!valid) {
        return;
    }

    error.value = null;

    try {
        const submitData = {
            name: data.name,
            createResponse: JSON.parse(data.createResponse)
        };

        await axios({
            method: 'PUT',
            url: registerWebAuthnUrl.value,
            data: submitData
        });

        hide();
    } catch (e) {
        error.value = getErrorAsString(e);
    }
};

defineExpose({
    create
});
</script>

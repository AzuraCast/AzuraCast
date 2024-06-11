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
                    :field="v$.name"
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
                    :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
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
import {required} from '@vuelidate/validators';
import {ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import {getApiUrl} from "~/router.ts";
import useWebAuthn from "~/functions/useWebAuthn.ts";

const emit = defineEmits(['relist']);

const registerWebAuthnUrl = getApiUrl('/frontend/account/webauthn/register');

const error = ref(null);

const {form, resetForm, v$, validate} = useVuelidateOnForm(
    {
        name: {required},
        createResponse: {required}
    },
    {
        name: '',
        createResponse: null
    }
);

const clearContents = () => {
    resetForm();
    error.value = null;
};

const $modal = ref<ModalTemplateRef>(null);
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
    const registerArgs = await axios.get(registerWebAuthnUrl.value).then(r => r.data);

    try {
        form.value.createResponse = await doRegister(registerArgs);
    } catch (err) {
        if (err.name === 'InvalidStateError') {
            error.value = 'Error: Authenticator was probably already registered by user';
        } else {
            error.value = err;
        }

        throw err;
    }
};

const doSubmit = async () => {
    const isValid = await validate();
    if (!isValid) {
        return;
    }

    error.value = null;

    axios({
        method: 'PUT',
        url: registerWebAuthnUrl.value,
        data: form.value
    }).then(() => {
        hide();
    }).catch((error) => {
        error.value = error.response.data.message;
    });
};

defineExpose({
    create
});
</script>

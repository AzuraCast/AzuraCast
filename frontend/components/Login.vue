<template>
    <div class="public-page">
        <div class="card p-2">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm">
                        <h2
                            v-if="hideProductName"
                            class="card-title text-center"
                        >
                            {{ $gettext('Welcome!') }}
                        </h2>
                        <h2
                            v-else
                            class="card-title text-center"
                        >
                            {{ $gettext('Welcome to AzuraCast!') }}
                        </h2>
                        <h3
                            v-if="instanceName"
                            class="card-subtitle text-center text-muted"
                        >
                            {{ instanceName }}
                        </h3>
                    </div>
                </div>

                <form
                    id="login-form"
                    action=""
                    method="post"
                >
                    <div class="form-group">
                        <label
                            for="username"
                            class="mb-2 d-flex align-items-center gap-2"
                        >
                            <icon-ic-email/>
                            <strong>
                                {{ $gettext('E-mail Address') }}
                            </strong>
                        </label>
                        <input
                            id="username"
                            type="email"
                            name="username"
                            class="form-control"
                            autocomplete="username webauthn"
                            :placeholder="$gettext('name@example.com')"
                            :aria-label="$gettext('E-mail Address')"
                            required
                            autofocus
                        >
                    </div>
                    <div class="form-group mt-3">
                        <label
                            for="password"
                            class="mb-2 d-flex align-items-center gap-2"
                        >
                            <icon-ic-vpn-key/>

                            <strong>{{ $gettext('Password') }}</strong>
                        </label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control"
                            autocomplete="current-password"
                            :placeholder="$gettext('Enter your password')"
                            :aria-label="$gettext('Password')"
                            required
                        >
                    </div>
                    <div class="form-group mt-4">
                        <div class="custom-control custom-checkbox">
                            <input
                                id="frm_remember_me"
                                type="checkbox"
                                name="remember"
                                value="1"
                                class="toggle-switch custom-control-input"
                            >
                            <label
                                for="frm_remember_me"
                                class="custom-control-label"
                            >
                                {{ $gettext('Remember me') }}
                            </label>
                        </div>
                    </div>
                    <div class="block-buttons mt-3 mb-3">
                        <button
                            type="submit"
                            role="button"
                            :title="$gettext('Sign In')"
                            class="btn btn-login btn-primary"
                        >
                            {{ $gettext('Sign In') }}
                        </button>
                    </div>
                </form>

                <form
                    v-if="passkeySupported"
                    id="webauthn-form"
                    ref="$webAuthnForm"
                    :action="webAuthnUrl"
                    method="post"
                >
                    <input
                        type="hidden"
                        name="validateData"
                        :value="validateData"
                    >

                    <div class="block-buttons mb-3">
                        <button
                            type="button"
                            role="button"
                            :title="$gettext('Sign In with Passkey')"
                            class="btn btn-sm btn-secondary"
                            @click="logInWithPasskey"
                        >
                            {{ $gettext('Sign In with Passkey') }}
                        </button>
                    </div>
                </form>

                <p class="text-center m-0">
                    {{ $gettext('Please log in to continue.') }}

                    <a :href="forgotPasswordUrl">
                        {{ $gettext('Forgot your password?') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import useWebAuthn, {ProcessedValidateResponse} from "~/functions/useWebAuthn.ts";
import {useAxios} from "~/vendor/axios.ts";
import {nextTick, onMounted, ref, useTemplateRef} from "vue";
import IconIcEmail from "~icons/ic/baseline-email";
import IconIcVpnKey from "~icons/ic/baseline-vpn-key";

const props = defineProps<{
    hideProductName: boolean,
    instanceName: string,
    forgotPasswordUrl: string,
    webAuthnUrl: string,
}>();

const {
    isSupported: passkeySupported,
    isConditionalSupported: passkeyConditionalSupported,
    doValidate
} = useWebAuthn();

const {axios} = useAxios();

const $webAuthnForm = useTemplateRef('$webAuthnForm');

const validateArgs = ref<object | null>(null);
const validateData = ref<string | null>(null);

const handleValidationResponse = async (validateResp: ProcessedValidateResponse) => {
    validateData.value = JSON.stringify(validateResp);
    await nextTick();
    $webAuthnForm.value?.submit();
}

const logInWithPasskey = async () => {
    if (validateArgs.value === null) {
        validateArgs.value = (await axios.get<object>(props.webAuthnUrl)).data;
    }

    try {
        const validateResp = await doValidate(validateArgs.value, false);
        await handleValidationResponse(validateResp);
    } catch (e) {
        console.error(e);
    }
};

onMounted(async () => {
    const isConditionalSupported = await passkeyConditionalSupported();
    if (!isConditionalSupported) {
        return;
    }

    // Call WebAuthn authentication
    validateArgs.value = (await axios.get<object>(props.webAuthnUrl)).data;

    try {
        const validateResp = await doValidate(validateArgs.value, true);
        await handleValidationResponse(validateResp);
    } catch (e) {
        console.error(e);
    }
});
</script>

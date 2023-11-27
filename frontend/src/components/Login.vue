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
                            <icon :icon="IconMail" />
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
                            <icon :icon="IconVpnKey" />
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
import Icon from "~/components/Common/Icon.vue";
import {IconMail, IconVpnKey} from "~/components/Common/icons.ts";
import useWebAuthn from "~/functions/useWebAuthn.ts";
import {useAxios} from "~/vendor/axios.ts";
import {nextTick, onMounted, ref} from "vue";

const props = defineProps({
    hideProductName: {
        type: Boolean,
        default: true
    },
    instanceName: {
        type: String,
        default: null
    },
    forgotPasswordUrl: {
        type: String,
        default: null
    },
    webAuthnUrl: {
        type: String,
        default: null
    }
});

const {isSupported: passkeySupported, processServerArgs, processValidateResponse} = useWebAuthn();

const {axios} = useAxios();

const $webAuthnForm = ref<HTMLFormElement | null>(null);

const validateArgs = ref<object | null>(null);
const validateData = ref<string | null>(null);

const handleValidationResponse = async (attResp) => {
    validateData.value = JSON.stringify(processValidateResponse(attResp));
    await nextTick();
    $webAuthnForm.value?.submit();
}

const logInWithPasskey = async () => {
    if (null === validateArgs.value) {
        validateArgs.value = await axios.get(props.webAuthnUrl).then(r => processServerArgs(r.data));
    }

    const attResp = await navigator.credentials.get(validateArgs.value);
    await handleValidationResponse(attResp);
};

onMounted(async () => {
    if (passkeySupported && window.PublicKeyCredential
        && PublicKeyCredential.isConditionalMediationAvailable) {
        // Check if conditional mediation is available.
        const isCMA = await PublicKeyCredential.isConditionalMediationAvailable();
        if (!isCMA) {
            return;
        }

        // Call WebAuthn authentication
        validateArgs.value = await axios.get(props.webAuthnUrl).then(r => processServerArgs(r.data));

        const attResp = await navigator.credentials.get({
            ...validateArgs.value,
            mediation: 'conditional'
        });

        await handleValidationResponse(attResp);
    }
});
</script>

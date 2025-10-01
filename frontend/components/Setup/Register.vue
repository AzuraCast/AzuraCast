<template>
    <div class="public-page">
        <section
            class="card"
            role="region"
            aria-labelledby="hdr_first_time_setup"
        >
            <div class="card-body p-4">
                <div class="row mb-2">
                    <div class="col-sm">
                        <h2
                            id="hdr_first_time_setup"
                            class="card-title mb-0 text-center"
                        >
                            {{ $gettext('AzuraCast First-Time Setup') }}
                        </h2>
                        <h3 class="text-center">
                            <small class="text-muted">
                                {{ $gettext('Welcome to AzuraCast!') }}
                            </small>
                        </h3>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm">
                        <p class="card-text">
                            {{ $gettext('Let\'s get started by creating your Super Administrator account.') }}
                        </p>
                        <p class="card-text">
                            {{
                                $gettext('This account will have full access to the system, and you\'ll automatically be logged in to it for the rest of setup.')
                            }}
                        </p>
                    </div>
                </div>

                <div
                    v-show="error != null"
                    class="alert alert-danger"
                >
                    {{ error }}
                </div>

                <form
                    id="login-form"
                    class="form vue-form"
                    action=""
                    method="post"
                >
                    <input
                        type="hidden"
                        name="csrf"
                        :value="csrf"
                    >

                    <form-group-field
                        id="username"
                        name="username"
                        class="mb-3"
                        label-class="mb-2"
                        :field="r$.username"
                        input-type="email"
                    >
                        <template #label>
                            <icon-ic-email class="me-1"/>
                            {{ $gettext('E-mail Address') }}
                        </template>
                    </form-group-field>

                    <form-group-field
                        id="password"
                        name="password"
                        class="mb-3"
                        label-class="mb-2"
                        :field="r$.password"
                        input-type="password"
                    >
                        <template #label>
                            <icon-ic-vpn-key class="me-1"/>
                            {{ $gettext('Password') }}
                        </template>
                    </form-group-field>

                    <div class="block-buttons mt-2">
                        <button
                            type="submit"
                            class="btn btn-block btn-primary"
                            :disabled="r$.$invalid"
                        >
                            {{ $gettext('Create Account') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {reactive} from "vue";
import {isValidPassword, useAppRegle} from "~/vendor/regle.ts";
import {email, required} from "@regle/rules";
import IconIcEmail from "~icons/ic/baseline-email";
import IconIcVpnKey from "~icons/ic/baseline-vpn-key";

withDefaults(
    defineProps<{
        csrf: string,
        error?: string | null
    }>(),
    {
        error: null
    }
);

const form = reactive({
    username: '',
    password: '',
});

const {r$} = useAppRegle(
    form,
    {
        username: {required, email},
        password: {required, isValidPassword}
    },
    {}
);
</script>

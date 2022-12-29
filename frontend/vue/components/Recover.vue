<template>
    <div class="public-page">
        <div class="card">
            <div class="card-body p-4">
                <div class="mb-3">
                    <h2 class="card-title mb-0 text-center">
                        {{ $gettext('Recover Account') }}
                    </h2>
                    <h3 class="text-center">
                        <small class="text-muted">
                            {{ $gettext('Choose a new password for your account.') }}
                        </small>
                    </h3>
                </div>

                <b-alert
                    variant="danger"
                    :show="error != null"
                >
                    {{ error }}
                </b-alert>

                <form
                    id="recover-form"
                    class="form vue-form"
                    action=""
                    method="post"
                >
                    <input
                        type="hidden"
                        name="csrf"
                        :value="csrf"
                    >

                    <b-wrapped-form-group
                        id="password"
                        name="password"
                        label-class="mb-2"
                        :field="v$.password"
                        input-type="password"
                    >
                        <template #label>
                            <icon
                                icon="vpn_key"
                                class="mr-1"
                            />
                            {{ $gettext('Password') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-button
                        type="submit"
                        size="lg"
                        block
                        variant="primary"
                        :disabled="v$.$invalid"
                        class="mt-2"
                    >
                        {{ $gettext('Recover Account') }}
                    </b-button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import Icon from "~/components/Common/Icon";
import validatePassword from '~/functions/validatePassword.js';
import {required} from '@vuelidate/validators';
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";

const props = defineProps({
    csrf: String,
    error: String,
});

const {form, v$} = useVuelidateOnForm(
    {
        password: {required, validatePassword}
    },
    {
        password: null,
    }
)
</script>

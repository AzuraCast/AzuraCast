<template>
    <div class="public-page">
        <section
            class="card"
            role="region"
            aria-labelledby="hdr_recover_account"
        >
            <div class="card-body p-4">
                <div class="mb-3">
                    <h2
                        id="hdr_recover_account"
                        class="card-title mb-0 text-center"
                    >
                        {{ $gettext('Recover Account') }}
                    </h2>
                    <h3 class="text-center">
                        <small class="text-muted">
                            {{ $gettext('Choose a new password for your account.') }}
                        </small>
                    </h3>
                </div>

                <div
                    v-show="error != null"
                    class="alert alert-danger"
                >
                    {{ error }}
                </div>

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

                    <form-group-field
                        id="password"
                        name="password"
                        label-class="mb-2"
                        :field="v$.password"
                        input-type="password"
                    >
                        <template #label>
                            <icon
                                :icon="IconVpnKey"
                                class="me-1"
                            />
                            {{ $gettext('Password') }}
                        </template>
                    </form-group-field>

                    <div class="block-buttons mt-2">
                        <button
                            type="submit"
                            class="btn btn-primary btn-block"
                            :disabled="v$.$invalid"
                        >
                            {{ $gettext('Recover Account') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import Icon from "~/components/Common/Icon.vue";
import validatePassword from '~/functions/validatePassword';
import {required} from '@vuelidate/validators';
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {IconVpnKey} from "~/components/Common/icons";

const props = defineProps({
    csrf: {
        type: String,
        required: true
    },
    error: {
        type: String,
        default: null
    },
});

const {v$} = useVuelidateOnForm(
    {
        password: {required, validatePassword}
    },
    {
        password: null,
    }
)
</script>

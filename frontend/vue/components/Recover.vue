<template>
    <div class="public-page">
        <div class="card">
            <div class="card-body p-4">
                <div class="mb-3">
                    <h2 class="card-title mb-0 text-center">
                        <translate key="lang_hdr">Recover Account</translate>
                    </h2>
                    <h3 class="text-center">
                        <small class="text-muted">
                            <translate key="lang_subhdr">Choose a new password for your account.</translate>
                        </small>
                    </h3>
                </div>

                <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

                <form id="recover-form" class="form vue-form" action="" method="post">
                    <input type="hidden" name="csrf" :value="csrf"/>

                    <b-wrapped-form-group id="password" name="password" label-class="mb-2" :field="$v.form.password"
                                          input-type="password">
                        <template #label="{lang}">
                            <icon icon="vpn_key" class="mr-1"></icon>
                            <translate :key="lang">Password</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-button type="submit" size="lg" block variant="primary" :disabled="$v.form.$invalid"
                              class="mt-2">
                        <translate key="btn_submit">Recover Account</translate>
                    </b-button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import {validationMixin} from "vuelidate";
import {required} from 'vuelidate/dist/validators.min.js';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import Icon from "~/components/Common/Icon";
import validatePassword from '~/functions/validatePassword.js';

export default {
    name: 'SetupRegister',
    components: {Icon, BWrappedFormGroup},
    mixins: [
        validationMixin
    ],
    props: {
        csrf: String,
        error: String,
    },
    validations() {
        return {
            form: {
                password: {required, validatePassword}
            }
        }
    },
    data() {
        return {
            form: {
                password: null,
            }
        }
    }
}
</script>

<template>
    <div class="public-page">
        <div class="card">
            <div class="card-body p-4">
                <div class="row mb-2">
                    <div class="col-sm">
                        <h2 class="card-title mb-0 text-center">
                            <translate key="lang_hdr_setup">AzuraCast First-Time Setup</translate>
                        </h2>
                        <h3 class="text-center">
                            <small class="text-muted">
                                <translate key="lang_subhdr_welcome">Welcome to AzuraCast!</translate>
                            </small>
                        </h3>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm">
                        <p class="card-text">
                            <translate key="lang_intro_1">Let's get started by creating your Super Administrator account.</translate>
                        </p>
                        <p class="card-text">
                            <translate key="lang_intro_2">This account will have full access to the system, and you'll automatically be logged in to it for the rest of setup.</translate>
                        </p>
                    </div>
                </div>

                <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

                <form id="login-form" class="form vue-form" action="" method="post">
                    <input type="hidden" name="csrf" :value="csrf"/>

                    <b-wrapped-form-group id="username" name="username" label-class="mb-2" :field="$v.form.username"
                                          input-type="email">
                        <template #label="{lang}">
                            <icon icon="email" class="mr-1"></icon>
                            <translate :key="lang">E-mail Address</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group id="password" name="password" label-class="mb-2" :field="$v.form.password"
                                          input-type="password">
                        <template #label="{lang}">
                            <icon icon="vpn_key" class="mr-1"></icon>
                            <translate :key="lang">Password</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-button type="submit" size="lg" block variant="primary" :disabled="$v.form.$invalid"
                              class="mt-2">
                        <translate key="btn_create_acct">Create Account</translate>
                    </b-button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import {validationMixin} from "vuelidate";
import {email, required} from 'vuelidate/dist/validators.min.js';
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
                username: {required, email},
                password: {required, validatePassword}
            }
        }
    },
    data() {
        return {
            form: {
                username: null,
                password: null,
            }
        }
    }
}
</script>

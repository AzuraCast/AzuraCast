<template>
    <b-modal id="send_test_message" centered ref="modal" :title="langTitle">
        <b-form @submit.prevent="doSendTest">
            <b-wrapped-form-group id="email_address" :field="v$.emailAddress" autofocus>
                <template #label="{lang}">
                    {{ $gettext('E-mail Address') }}
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" @click="close">
                {{ $gettext('Close') }}
            </b-button>
            <b-button :variant="(v$.$invalid) ? 'danger' : 'primary'" @click="doSendTest">
                {{ $gettext('Send Test Message') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import {email, required} from '@vuelidate/validators';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'AdminSettingsTestMessageModal',
    components: {BWrappedFormGroup},
    setup() {
        return {v$: useVuelidate()}
    },
    props: {
        testMessageUrl: String
    },
    data() {
        return {
            emailAddress: null
        };
    },
    validations: {
        emailAddress: {required, email}
    },
    computed: {
        langTitle() {
            return this.$gettext('Send Test Message');
        },
    },
    methods: {
        close() {
            this.emailAddress = null;
            this.v$.$reset();
            this.$refs.modal.hide();
        },
        async doSendTest() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
                return;
            }

            this.$wrapWithLoading(
                this.axios.post(this.testMessageUrl, {
                    'email': this.emailAddress
                })
            ).then(() => {
                this.$notifySuccess(this.$gettext('Test message sent.'));
            }).finally(() => {
                this.close();
            });
        }
    }
}
</script>

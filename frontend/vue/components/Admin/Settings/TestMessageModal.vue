<template>
    <b-modal id="send_test_message" centered ref="modal" :title="langTitle">
        <b-form @submit.prevent="doSendTest">
            <b-wrapped-form-group id="email_address" :field="$v.emailAddress" autofocus>
                <template #label="{lang}">
                    <translate :key="lang">E-mail Address</translate>
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button :variant="($v.$invalid) ? 'danger' : 'primary'" @click="doSendTest">
                <translate key="lang_btn_send">Send Test Message</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {validationMixin} from "vuelidate";
import {email, required} from 'vuelidate/dist/validators.min.js';

export default {
    name: 'AdminSettingsTestMessageModal',
    components: {BWrappedFormGroup},
    mixins: [validationMixin],
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
            this.$v.$reset();
            this.$refs.modal.hide();
        },
        doSendTest() {
            this.$v.$touch();
            if (this.$v.$anyError) {
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

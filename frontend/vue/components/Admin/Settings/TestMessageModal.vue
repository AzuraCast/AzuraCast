<template>
    <b-modal id="send_test_message" centered ref="modal" :title="$gettext('Send Test Message')">
        <b-form @submit.prevent="doSendTest">
            <b-wrapped-form-group id="email_address" :field="v$.emailAddress" autofocus>
                <template #label>
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

<script setup>
import useVuelidate from "@vuelidate/core";
import {email, required} from '@vuelidate/validators';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {ref} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
import gettext from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    testMessageUrl: String
});

const blankForm = {
    emailAddress: null
};

const form = ref({...blankForm});

const validations = {
    emailAddress: {required, email}
};

const v$ = useVuelidate(validations, form);

const resetForm = () => {
    form.value = {...blankForm};
};

const modal = ref(); // BModal

const close = () => {
    v$.value.reset();
    modal.value.hide();
}

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = gettext;

const doSendTest = () => {
    v$.value.$touch();

    if (v$.value.$errors.length > 0) {
        return;
    }

    wrapWithLoading(
        axios.post(props.testMessageUrl, {
            'email': form.value.emailAddress
        })
    ).then(() => {
        notifySuccess($gettext('Test message sent.'));
    }).finally(() => {
        close();
    });
};
</script>

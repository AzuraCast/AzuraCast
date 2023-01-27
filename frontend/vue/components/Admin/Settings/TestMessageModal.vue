<template>
    <b-modal
        id="send_test_message"
        ref="$modal"
        centered
        :title="$gettext('Send Test Message')"
    >
        <b-form @submit.prevent="doSendTest">
            <b-wrapped-form-group
                id="email_address"
                :field="v$.emailAddress"
                autofocus
            >
                <template #label>
                    {{ $gettext('E-mail Address') }}
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <b-button
                variant="default"
                @click="close"
            >
                {{ $gettext('Close') }}
            </b-button>
            <b-button
                :variant="(v$.$invalid) ? 'danger' : 'primary'"
                @click="doSendTest"
            >
                {{ $gettext('Send Test Message') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script setup>
import {email, required} from '@vuelidate/validators';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {ref} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {BModal} from "bootstrap-vue";

const props = defineProps({
    testMessageUrl: {
        type: String,
        required: true
    }
});

const {form, v$, ifValid} = useVuelidateOnForm(
    {
        emailAddress: {required, email}
    },
    {
        emailAddress: null
    },
    {
        $stopPropagation: true
    }
);

const $modal = ref(); // BModal

const close = () => {
    v$.value.reset();
    $modal.value.hide();
}

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = useTranslate();

const doSendTest = () => {
    ifValid(() => {
        wrapWithLoading(
            axios.post(props.testMessageUrl, {
                'email': form.value.emailAddress
            })
        ).then(() => {
            notifySuccess($gettext('Test message sent.'));
        }).finally(() => {
            close();
        });
    });
};
</script>

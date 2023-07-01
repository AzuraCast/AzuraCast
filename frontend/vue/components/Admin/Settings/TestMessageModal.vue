<template>
    <b-modal
        id="send_test_message"
        ref="$modal"
        centered
        :title="$gettext('Send Test Message')"
    >
        <b-form @submit.prevent="doSendTest">
            <form-group-field
                id="email_address"
                :field="v$.emailAddress"
                autofocus
            >
                <template #label>
                    {{ $gettext('E-mail Address') }}
                </template>
            </form-group-field>
        </b-form>
        <template #modal-footer>
            <button
                class="btn btn-secondary"
                @click="close"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                class="btn"
                :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doSendTest"
            >
                {{ $gettext('Send Test Message') }}
            </button>
        </template>
    </b-modal>
</template>

<script setup>
import {email, required} from '@vuelidate/validators';
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";

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

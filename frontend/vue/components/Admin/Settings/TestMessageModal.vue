<template>
    <modal
        id="send_test_message"
        ref="$modal"
        centered
        :title="$gettext('Send Test Message')"
        @hidden="resetForm()"
    >
        <form @submit.prevent="doSendTest">
            <form-group-field
                id="email_address"
                :field="v$.emailAddress"
                autofocus
                :label="$gettext('E-mail Address')"
            />
        </form>
        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="hide"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                type="button"
                class="btn"
                :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doSendTest"
            >
                {{ $gettext('Send Test Message') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {email, required} from '@vuelidate/validators';
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import Modal from "~/components/Common/Modal.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const props = defineProps({
    testMessageUrl: {
        type: String,
        required: true
    }
});

const {form, v$, resetForm, ifValid} = useVuelidateOnForm(
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

const $modal = ref<ModalTemplateRef>(null);
const {show: open, hide} = useHasModal($modal);

const {notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = useTranslate();

const doSendTest = () => {
    ifValid(() => {
        axios.post(props.testMessageUrl, {
            'email': form.value.emailAddress
        }).then(() => {
            notifySuccess($gettext('Test message sent.'));
        }).finally(() => {
            close();
        });
    });
};

defineExpose({
    open
});
</script>

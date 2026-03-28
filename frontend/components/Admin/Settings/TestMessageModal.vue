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
                :field="r$.emailAddress"
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
                :class="(r$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doSendTest"
            >
                {{ $gettext('Send Test Message') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {email, required} from "@regle/rules";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {useTemplateRef} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const testMessageUrl = getApiUrl('/admin/send-test-message');

type TestMessageRecord = {
    emailAddress: string
}

const {record: form, reset: resetFormRef} = useResettableRef<TestMessageRecord>({
    emailAddress: ''
});

const {r$} = useAppRegle(
    form,
    {
        emailAddress: {required, email}
    },
    {}
);

const resetForm = () => {
    resetFormRef();
    r$.$reset();
}

const $modal = useTemplateRef('$modal');
const {show: open, hide} = useHasModal($modal);

const {notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = useTranslate();

const doSendTest = async () => {
    const {valid} = await r$.$validate();
    if (!valid) {
        return;
    }

    try {
        await axios.post(testMessageUrl.value, {
            'email': form.value.emailAddress
        });

        notifySuccess($gettext('Test message sent.'));
    } finally {
        hide();
    }
};

defineExpose({
    open
});
</script>

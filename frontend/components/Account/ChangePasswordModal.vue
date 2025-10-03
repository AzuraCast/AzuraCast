<template>
    <modal-form
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Change Password')"
        :disable-save-button="r$.$invalid"
        @submit="onSubmit"
        @hidden="clearContents"
    >
        <form-group-field
            id="form_current_password"
            :field="r$.current_password"
            input-type="password"
            autofocus
            :label="$gettext('Current Password')"
            :input-attrs="{
                autocomplete: 'current-password'
            }"
            class="mb-3"
        />

        <form-group-field
            id="form_new_password"
            :field="r$.new_password"
            input-type="password"
            :label="$gettext('New Password')"
            :input-attrs="{
                autocomplete: 'new-password'
            }"
            class="mb-3"
        />

        <form-group-field
            id="form_confirm_new_password"
            :field="r$.new_password2"
            input-type="password"
            :input-attrs="{
                autocomplete: 'new-password'
            }"
            :label="$gettext('Confirm New Password')"
        />

        <template #save-button-name>
            {{ $gettext('Change Password') }}
        </template>
    </modal-form>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import {ref, useTemplateRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {isValidPassword, useAppRegle} from "~/vendor/regle.ts";
import {required, withMessage} from "@regle/rules";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const emit = defineEmits<HasRelistEmit>();

const {getApiUrl} = useApiRouter();
const changePasswordUrl = getApiUrl('/frontend/account/password');

const {$gettext} = useTranslate();

const {r$} = useAppRegle(
    {
        current_password: '',
        new_password: '',
        new_password2: ''
    },
    {
        current_password: {required},
        new_password: {required, isValidPassword},
        new_password2: {
            required,
            passwordsMatch: withMessage(
                (value, siblings) => {
                    return siblings.new_password === value;
                },
                $gettext('Must match new password.')
            )
        }
    },
    {}
);

const error = ref(null);

const clearContents = () => {
    r$.$reset({
        toOriginalState: true
    });

    error.value = null;
};

const $modal = useTemplateRef('$modal');

const open = () => {
    clearContents();
    $modal.value?.show();
};

const {axios} = useAxios();

const onSubmit = async () => {
    const {valid, data: postData} = await r$.$validate();
    if (!valid) {
        return;
    }

    try {
        await axios.put(changePasswordUrl.value, postData);
    } finally {
        $modal.value?.hide();
        emit('relist');
    }
};

defineExpose({
    open
});
</script>

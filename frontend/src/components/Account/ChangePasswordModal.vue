<template>
    <modal-form
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Change Password')"
        :disable-save-button="v$.$invalid"
        @submit="onSubmit"
        @hidden="clearContents"
    >
        <form-group-field
            id="form_current_password"
            :field="v$.current_password"
            input-type="password"
            autofocus
            :label="$gettext('Current Password')"
        />

        <form-group-field
            id="form_new_password"
            :field="v$.new_password"
            input-type="password"
            :label="$gettext('New Password')"
        />

        <form-group-field
            id="form_current_password"
            :field="v$.new_password2"
            input-type="password"
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
import {helpers, required} from "@vuelidate/validators";
import validatePassword from "~/functions/validatePassword";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import {ModalFormTemplateRef} from "~/functions/useBaseEditModal.ts";
import {getApiUrl} from "~/router.ts";

const emit = defineEmits(['relist']);

const changePasswordUrl = getApiUrl('/frontend/account/password');

const passwordsMatch = (value, siblings) => {
    return siblings.new_password === value;
};

const {$gettext} = useTranslate();

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        current_password: {required},
        new_password: {required, validatePassword},
        new_password2: {
            required,
            passwordsMatch: helpers.withMessage($gettext('Must match new password.'), passwordsMatch)
        }
    },
    {
        current_password: null,
        new_password: null,
        new_password2: null
    }
);

const error = ref(null);

const clearContents = () => {
    error.value = null;
    resetForm();
};

const $modal = ref<ModalFormTemplateRef>(null);

const open = () => {
    clearContents();
    $modal.value?.show();
};

const {axios} = useAxios();

const onSubmit = () => {
    ifValid(() => {
        axios
            .put(changePasswordUrl.value, form.value)
            .finally(() => {
                $modal.value?.hide();
                emit('relist');
            });
    });
};

defineExpose({
    open
});
</script>

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

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import ModalForm from "~/components/Common/ModalForm";
import {helpers, required} from "@vuelidate/validators";
import validatePassword from "~/functions/validatePassword";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    changePasswordUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

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

const $modal = ref(); // ModalForm

const open = () => {
    clearContents();
    $modal.value.show();
};

const {wrapWithLoading} = useNotify();
const {axios} = useAxios();

const onSubmit = () => {
    ifValid(() => {
        wrapWithLoading(
            axios.put(props.changePasswordUrl, form.value)
        ).finally(() => {
            $modal.value.hide();
            emit('relist');
        });
    });
};

defineExpose({
    open
});
</script>

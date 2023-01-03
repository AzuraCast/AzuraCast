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
        <b-form-fieldset>
            <b-wrapped-form-group
                id="form_current_password"
                :field="v$.current_password"
                input-type="password"
                autofocus
            >
                <template #label>
                    {{ $gettext('Current Password') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_new_password"
                :field="v$.new_password"
                input-type="password"
            >
                <template #label>
                    {{ $gettext('New Password') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_current_password"
                :field="v$.new_password2"
                input-type="password"
            >
                <template #label>
                    {{ $gettext('Confirm New Password') }}
                </template>
            </b-wrapped-form-group>
        </b-form-fieldset>

        <template #save-button-name>
            {{ $gettext('Change Password') }}
        </template>
    </modal-form>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import ModalForm from "~/components/Common/ModalForm";
import BFormFieldset from "~/components/Form/BFormFieldset";
import {helpers, required} from "@vuelidate/validators";
import validatePassword from "~/functions/validatePassword";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
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

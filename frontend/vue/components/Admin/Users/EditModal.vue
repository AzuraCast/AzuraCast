<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <admin-users-form
            :form="v$"
            :roles="roles"
            :is-edit-mode="isEditMode"
        />
    </modal-form>
</template>

<script setup>
import {email, required} from '@vuelidate/validators';
import AdminUsersForm from './Form.vue';
import {map} from 'lodash';
import validatePassword from "~/functions/validatePassword";
import {computed, ref} from "vue";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
    roles: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['relist']);

const $modal = ref(); // Template Ref

const {
    loading,
    error,
    isEditMode,
    v$,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    props,
    emit,
    $modal,
    (formRef, formIsEditMode) => computed(() => {
        return {
            name: {},
            new_password: (formIsEditMode.value)
                ? {validatePassword}
                : {required, validatePassword},
            email: {required, email},
            roles: {}
        }
    }),
    {
        name: '',
        email: '',
        new_password: '',
        roles: [],
    },
    {
        populateForm: (data, formRef) => {
            formRef.value = {
                name: data.name,
                email: data.email,
                new_password: '',
                roles: map(data.roles, 'id')
            };
        },
    }
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit User')
        : $gettext('Add User');
});

defineExpose({
    create,
    edit,
    close
});
</script>

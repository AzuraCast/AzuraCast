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
            v-model:form="form"
            :roles="roles"
            :is-edit-mode="isEditMode"
        />
    </modal-form>
</template>

<script setup lang="ts">
import AdminUsersForm from './Form.vue';
import {map} from 'lodash';
import {computed, ref} from "vue";
import {baseEditModalProps, ModalFormTemplateRef, useBaseEditModal} from "~/functions/useBaseEditModal";
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

const $modal = ref<ModalFormTemplateRef>(null);

const {
    loading,
    error,
    isEditMode,
    form,
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
    {},
    {},
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

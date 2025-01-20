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
import {
    BaseEditModalEmits,
    BaseEditModalProps,
    ModalFormTemplateRef,
    useBaseEditModal
} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import mergeExisting from "~/functions/mergeExisting.ts";

interface UsersEditModalProps extends BaseEditModalProps {
    roles: Record<number, string>
}

const props = defineProps<UsersEditModalProps>();
const emit = defineEmits<BaseEditModalEmits>();

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
    {
        roles: {},
        new_password: {}
    },
    {
        roles: [],
        new_password: null
    },
    {
        populateForm: (data, formRef) => {
            formRef.value = mergeExisting(
                formRef.value,
                {
                    ...data,
                    roles: map(data.roles, 'id'),
                    new_password: ''
                }
            );
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

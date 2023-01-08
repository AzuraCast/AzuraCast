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
        <sftp-users-form
            :form="v$"
            :is-edit-mode="isEditMode"
        />
    </modal-form>
</template>

<script setup>
import SftpUsersForm from "./Form";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {required} from "@vuelidate/validators";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
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
            username: {required},
            password: formIsEditMode.value ? {} : {required},
            publicKeys: {}
        }
    }),
    {
        username: '',
        password: null,
        publicKeys: null
    },
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit SFTP User')
        : $gettext('Add SFTP User');
});

defineExpose({
    create,
    edit,
    close
});
</script>

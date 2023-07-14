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
            v-model:form="form"
            :is-edit-mode="isEditMode"
        />
    </modal-form>
</template>

<script setup>
import SftpUsersForm from "./Form";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
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
    {}
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

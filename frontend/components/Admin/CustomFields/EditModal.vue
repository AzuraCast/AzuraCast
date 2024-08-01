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
        <admin-custom-fields-form
            :form="v$"
            :auto-assign-types="autoAssignTypes"
        />
    </modal-form>
</template>

<script setup lang="ts">
import {required} from '@vuelidate/validators';
import ModalForm from "~/components/Common/ModalForm.vue";
import AdminCustomFieldsForm from "~/components/Admin/CustomFields/Form.vue";
import {computed, ref} from "vue";
import {baseEditModalProps, ModalFormTemplateRef, useBaseEditModal} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    ...baseEditModalProps,
    autoAssignTypes: {
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
        'name': {required},
        'short_name': {},
        'auto_assign': {}
    },
    {
        'name': '',
        'short_name': '',
        'auto_assign': ''
    },
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Custom Field')
        : $gettext('Add Custom Field');
});

defineExpose({
    create,
    edit,
    close
});
</script>

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
        <b-tabs
            content-class="mt-3"
            pills
        >
            <form-basic-info :form="v$" />
        </b-tabs>
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import FormBasicInfo from './Form/BasicInfo';
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
});

const emit = defineEmits(['relist', 'needs-restart']);

const $modal = ref(); // Template Ref

const {notifySuccess} = useNotify();

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
        name: {required},
        format: {required},
        bitrate: {required}
    },
    {
        name: null,
        format: 'aac',
        bitrate: 128
    },
    {
        onSubmitSuccess: () => {
            notifySuccess();
            emit('relist');
            emit('needs-restart');
            close();
        },
    }
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit HLS Stream')
        : $gettext('Add HLS Stream');
});

defineExpose({
    create,
    edit,
    close
});
</script>

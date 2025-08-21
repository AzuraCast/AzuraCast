<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <tabs content-class="mt-3">
            <form-basic-info/>
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import FormBasicInfo from "~/components/Stations/HlsStreams/Form/BasicInfo.vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, useTemplateRef} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {storeToRefs} from "pinia";
import {useStationsHlsStreamsForm} from "~/components/Stations/HlsStreams/Form/form.ts";

const props = defineProps<BaseEditModalProps>();

const emit = defineEmits<BaseEditModalEmits & {
    (e: 'needs-restart'): void
}>();

const $modal = useTemplateRef('$modal');

const {notifySuccess} = useNotify();

const formStore = useStationsHlsStreamsForm();
const {form, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    form,
    props,
    emit,
    $modal,
    resetForm,
    async () => (await r$.value.$validate()).valid,
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

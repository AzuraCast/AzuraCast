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
        <tabs>
            <remote-form-basic-info/>

            <remote-form-auto-dj/>
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import RemoteFormBasicInfo from "~/components/Stations/Remotes/Form/BasicInfo.vue";
import RemoteFormAutoDj from "~/components/Stations/Remotes/Form/AutoDj.vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, toRef, useTemplateRef} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {storeToRefs} from "pinia";
import {StationRemotesRecord, useStationsRemotesForm} from "~/components/Stations/Remotes/Form/form.ts";
import mergeExisting from "~/functions/mergeExisting.ts";

const props = defineProps<BaseEditModalProps>();

const emit = defineEmits<BaseEditModalEmits & {
    (e: 'needs-restart'): void
}>();

const $modal = useTemplateRef('$modal');

const {notifySuccess} = useNotify();

const formStore = useStationsRemotesForm();
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
} = useBaseEditModal<StationRemotesRecord>(
    toRef(props, 'createUrl'),
    emit,
    $modal,
    resetForm,
    (data) => {
        r$.value.$reset({
            toState: mergeExisting(r$.value.$value, data)
        })
    },
    async () => {
        const {valid} = await r$.value.$validate();
        return {valid, data: form.value};
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
        ? $gettext('Edit Remote Relay')
        : $gettext('Add Remote Relay');
});

defineExpose({
    create,
    edit,
    close
});
</script>

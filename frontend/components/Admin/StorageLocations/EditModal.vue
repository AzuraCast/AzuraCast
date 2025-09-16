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
            <storage-location-form v-model:form="form" />

            <s3
                v-if="form.adapter === 's3'"
                v-model:form="form"
            />

            <dropbox
                v-if="form.adapter === 'dropbox'"
                v-model:form="form"
            />

            <sftp
                v-if="form.adapter === 'sftp'"
                v-model:form="form"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, toRef, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import StorageLocationForm from "~/components/Admin/StorageLocations/Form.vue";
import Sftp from "~/components/Admin/StorageLocations/Form/Sftp.vue";
import S3 from "~/components/Admin/StorageLocations/Form/S3.vue";
import Dropbox from "~/components/Admin/StorageLocations/Form/Dropbox.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {StorageLocationRecord, useAdminStorageLocationsForm} from "~/components/Admin/StorageLocations/Form/form.ts";
import {storeToRefs} from "pinia";
import mergeExisting from "~/functions/mergeExisting.ts";

const props = defineProps<BaseEditModalProps & {
    type: string
}>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal');

const formStore = useAdminStorageLocationsForm();
const {form, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

type RecordWithType = StorageLocationRecord & {
    type?: string
};

const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal<RecordWithType>(
    toRef(props, 'createUrl'),
    emit,
    $modal,
    resetForm,
    (data) => {
        r$.value.$reset({
            toState: mergeExisting(r$.value.$value, data)
        })
    },
    async (isEditMode) => {
        const {valid, data} = await r$.value.$validate();
        if (!valid || !data) {
            return {valid};
        }

        if (isEditMode) {
            return {valid, data: data as RecordWithType};
        }

        return {
            valid,
            data: {
                ...data,
                type: props.type
            } as RecordWithType
        };
    }
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Storage Location')
        : $gettext('Add Storage Location');
});

defineExpose({
    create,
    edit,
    close
});
</script>

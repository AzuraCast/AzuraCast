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
import {
    BaseEditModalEmits,
    BaseEditModalProps,
    ModalFormTemplateRef,
    useBaseEditModal
} from "~/functions/useBaseEditModal";
import {computed, nextTick, ref, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import StorageLocationForm from "./Form.vue";
import Sftp from "~/components/Admin/StorageLocations/Form/Sftp.vue";
import S3 from "~/components/Admin/StorageLocations/Form/S3.vue";
import Dropbox from "~/components/Admin/StorageLocations/Form/Dropbox.vue";
import Tabs from "~/components/Common/Tabs.vue";
import mergeExisting from "~/functions/mergeExisting.ts";

interface StorageLocationsEditModalProps extends BaseEditModalProps {
    type: string
}

const props = defineProps<StorageLocationsEditModalProps>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = ref<ModalFormTemplateRef>(null);

const {
    loading,
    error,
    isEditMode,
    form,
    v$,
    resetForm,
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
        adapter: {},
    },
    {
        adapter: 'local',
    },
    {
        populateForm: (data, formRef) => {
            formRef.value.adapter = data.adapter;
            
            nextTick(() => {
                resetForm();
                formRef.value = mergeExisting(formRef.value, data);
            });
        },
        getSubmittableFormData: (formRef, isEditModeRef) => {
            if (isEditModeRef.value) {
                return formRef.value;
            }

            return {
                ...formRef.value,
                type: props.type
            };
        }
    }
);

watch(
    () => form.value.adapter,
    () => {
        if (!isEditMode.value) {
            const originalForm = form.value;

            nextTick(() => {
                resetForm();
                form.value = mergeExisting(form.value, originalForm);
            });
        }

    }
)

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

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

<script setup>
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import StorageLocationForm from "./Form.vue";
import Sftp from "~/components/Admin/StorageLocations/Form/Sftp.vue";
import S3 from "~/components/Admin/StorageLocations/Form/S3.vue";
import Dropbox from "~/components/Admin/StorageLocations/Form/Dropbox.vue";
import Tabs from "~/components/Common/Tabs.vue";

const props = defineProps({
    ...baseEditModalProps,
    type: {
        type: String,
        required: true
    }
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
    {
        // These have to be defined here because the sub-items conditionally render.
        dropboxAppKey: null,
        dropboxAppSecret: null,
        dropboxAuthToken: null,
        s3CredentialKey: null,
        s3CredentialSecret: null,
        s3Region: null,
        s3Version: 'latest',
        s3Bucket: null,
        s3Endpoint: null,
        sftpHost: null,
        sftpPort: '22',
        sftpUsername: null,
        sftpPassword: null,
        sftpPrivateKey: null,
        sftpPrivateKeyPassPhrase: null,
    },
    {
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

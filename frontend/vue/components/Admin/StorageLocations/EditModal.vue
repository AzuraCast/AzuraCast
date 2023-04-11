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
        <storage-location-form :form="v$" />
    </modal-form>
</template>

<script setup>
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {required} from "@vuelidate/validators";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import StorageLocationForm from "./Form.vue";

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
    (formRef) => computed(() => {
        let validationRules = {
            'adapter': {required},
            'storageQuota': {},
            'path': {},
            's3CredentialKey': {},
            's3CredentialSecret': {},
            's3Region': {},
            's3Version': {},
            's3Bucket': {},
            's3Endpoint': {},
            'dropboxAppKey': {},
            'dropboxAppSecret': {},
            'dropboxAuthToken': {},
            'sftpHost': {},
            'sftpPort': {},
            'sftpUsername': {},
            'sftpPassword': {},
            'sftpPrivateKey': {},
            'sftpPrivateKeyPassPhrase': {}
        };

        switch (formRef.value.adapter) {
            case 'local':
                validationRules.path = {required};
                break;

            case 'dropbox':
                validationRules.dropboxAuthToken = {required};
                break;

            case 's3':
                validationRules.s3CredentialKey = {required};
                validationRules.s3CredentialSecret = {required};
                validationRules.s3Region = {required};
                validationRules.s3Version = {required};
                validationRules.s3Bucket = {required};
                validationRules.s3Endpoint = {required};
                break;

            case 'sftp':
                validationRules.sftpHost = {required};
                validationRules.sftpPort = {required};
                validationRules.sftpUsername = {required};
                break;
        }

        return validationRules;
    }),
    {
        'adapter': 'local',
        'path': '',
        's3CredentialKey': null,
        's3CredentialSecret': null,
        's3Region': null,
        's3Version': 'latest',
        's3Bucket': null,
        's3Endpoint': null,
        'dropboxAppKey': null,
        'dropboxAppSecret': null,
        'dropboxAuthToken': null,
        'sftpHost': null,
        'sftpPort': '22',
        'sftpUsername': null,
        'sftpPassword': null,
        'sftpPrivateKey': null,
        'sftpPrivateKeyPassPhrase': null,
        'storageQuota': ''
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

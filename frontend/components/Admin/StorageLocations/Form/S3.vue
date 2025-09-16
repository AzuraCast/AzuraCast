<template>
    <tab
        :label="$gettext('Remote: S3 Compatible')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_s3CredentialKey"
                class="col-md-6"
                :field="r$.s3CredentialKey"
                :label="$gettext('Access Key ID')"
            />

            <form-group-field
                id="form_edit_s3CredentialSecret"
                class="col-md-6"
                :field="r$.s3CredentialSecret"
                :label="$gettext('Secret Key')"
            />

            <form-group-field
                id="form_edit_s3Bucket"
                class="col-md-6"
                :field="r$.s3Bucket"
                :label="$gettext('Bucket Name')"
            />

            <form-group-field
                id="form_edit_s3Region"
                class="col-md-6"
                :field="r$.s3Region"
                :label="$gettext('Region')"
            />

            <form-group-field
                id="form_edit_s3Endpoint"
                class="col-md-6"
                :field="r$.s3Endpoint"
                :label="$gettext('Endpoint')"
            />

            <form-group-field
                id="form_edit_s3Version"
                class="col-md-6"
                :field="r$.s3Version"
                :label="$gettext('API Version')"
            />

            <form-group-checkbox
                id="form_edit_s3UsePathStyle"
                class="col-md-12"
                :field="r$.s3UsePathStyle"
                :label="$gettext('Use Path Instead of Subdomain Endpoint Style')"
                :description="$gettext('Enable this option if your S3 provider is using paths instead of sub-domains for their S3 endpoint; for example, when using MinIO or with other self-hosted S3 storage solutions that are accessible via a path on a domain/IP instead of a subdomain.')"
                advanced
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import Tab from "~/components/Common/Tab.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useAdminStorageLocationsForm} from "~/components/Admin/StorageLocations/Form/form.ts";
import {storeToRefs} from "pinia";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {computed} from "vue";

const formStore = useAdminStorageLocationsForm();
const {r$} = storeToRefs(formStore);

const tabClass = useFormTabClass(computed(() => r$.value.$groups.s3Tab));
</script>

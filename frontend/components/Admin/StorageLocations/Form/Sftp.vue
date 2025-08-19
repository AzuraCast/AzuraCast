<template>
    <tab
        :label="$gettext('Remote: SFTP')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_sftpHost"
                class="col-md-12 col-lg-6"
                :field="r$.sftpHost"
                :label="$gettext('SFTP Host')"
            />

            <form-group-field
                id="form_edit_sftpPort"
                class="col-md-12 col-lg-6"
                input-type="number"
                min="1"
                step="1"
                :field="r$.sftpPort"
                :label="$gettext('SFTP Port')"
            />

            <form-group-field
                id="form_edit_sftpUsername"
                class="col-md-12 col-lg-6"
                :field="r$.sftpUsername"
                :label="$gettext('SFTP Username')"
            />

            <form-group-field
                id="form_edit_sftpPassword"
                class="col-md-12 col-lg-6"
                :field="r$.sftpPassword"
                :label="$gettext('SFTP Password')"
            />

            <form-group-field
                id="form_edit_sftpPrivateKeyPassPhrase"
                class="col-md-12"
                :field="r$.sftpPrivateKeyPassPhrase"
                :label="$gettext('SFTP Private Key Pass Phrase')"
            />

            <form-group-field
                id="form_edit_sftpPrivateKey"
                class="col-md-12"
                input-type="textarea"
                :field="r$.sftpPrivateKey"
                :label="$gettext('SFTP Private Key')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import Tab from "~/components/Common/Tab.vue";
import {useAdminStorageLocationsForm} from "~/components/Admin/StorageLocations/Form/form.ts";
import {storeToRefs} from "pinia";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {computed} from "vue";

const formStore = useAdminStorageLocationsForm();
const {r$} = storeToRefs(formStore);

const tabClass = useFormTabClass(computed(() => r$.value.$groups.sftpTab));
</script>

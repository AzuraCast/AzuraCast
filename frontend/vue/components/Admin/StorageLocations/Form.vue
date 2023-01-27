<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_edit_adapter"
                class="col-md-12"
                :field="form.adapter"
            >
                <template #label>
                    {{ $gettext('Storage Adapter') }}
                </template>
                <template #default="slotProps">
                    <b-form-radio-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                    >
                        <b-form-radio value="local">
                            {{ $gettext('Local Filesystem') }}
                        </b-form-radio>
                        <b-form-radio value="s3">
                            {{ $gettext('Remote: S3 Compatible') }}
                        </b-form-radio>
                        <b-form-radio value="dropbox">
                            {{ $gettext('Remote: Dropbox') }}
                        </b-form-radio>
                        <b-form-radio value="sftp">
                            {{ $gettext('Remote: SFTP') }}
                        </b-form-radio>
                    </b-form-radio-group>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_edit_path"
                class="col-md-12"
                :field="form.path"
            >
                <template #label>
                    {{ $gettext('Path/Suffix') }}
                </template>
                <template #description>
                    {{
                        $gettext('For local filesystems, this is the base path of the directory. For remote filesystems, this is the folder prefix.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_edit_storageQuota"
                class="col-md-12"
                :field="form.storageQuota"
            >
                <template #label>
                    {{ $gettext('Storage Quota') }}
                </template>
                <template #description>
                    {{
                        $gettext('Set a maximum disk space that this storage location can use. Specify the size with unit, i.e. "8 GB". Units are measured in 1024 bytes. Leave blank to default to the available space on the disk.')
                    }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>

    <b-card
        v-show="form.adapter.$model === 's3'"
        class="mb-3"
        no-body
    >
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Remote: S3 Compatible') }}
            </h2>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
                    <b-wrapped-form-group
                        id="form_edit_s3CredentialKey"
                        class="col-md-6"
                        :field="form.s3CredentialKey"
                    >
                        <template #label>
                            {{ $gettext('Access Key ID') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3CredentialSecret"
                        class="col-md-6"
                        :field="form.s3CredentialSecret"
                    >
                        <template #label>
                            {{ $gettext('Secret Key') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3Endpoint"
                        class="col-md-6"
                        :field="form.s3Endpoint"
                    >
                        <template #label>
                            {{ $gettext('Endpoint') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3Bucket"
                        class="col-md-6"
                        :field="form.s3Bucket"
                    >
                        <template #label>
                            {{ $gettext('Bucket Name') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3Region"
                        class="col-md-6"
                        :field="form.s3Region"
                    >
                        <template #label>
                            {{ $gettext('Region') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3Version"
                        class="col-md-6"
                        :field="form.s3Version"
                    >
                        <template #label>
                            {{ $gettext('API Version') }}
                        </template>
                    </b-wrapped-form-group>
                </div>
            </b-form-group>
        </b-card-body>
    </b-card>

    <b-card
        v-show="form.adapter.$model === 'dropbox'"
        class="mb-3"
        no-body
    >
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Remote: Dropbox') }}
            </h2>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
                    <b-wrapped-form-group
                        id="form_edit_dropboxAuthToken"
                        class="col-md-12"
                        :field="form.dropboxAuthToken"
                    >
                        <template #label>
                            {{ $gettext('Dropbox Generated Access Token') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('Note: Dropbox now only issues short-lived tokens that will not work for this purpose. If your token begins with "sl", it is short-lived and will not work correctly.')
                            }}
                        </template>
                    </b-wrapped-form-group>
                </div>
            </b-form-group>
        </b-card-body>
    </b-card>

    <b-card
        v-show="form.adapter.$model === 'sftp'"
        class="mb-3"
        no-body
    >
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Remote: SFTP') }}
            </h2>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
                    <b-wrapped-form-group
                        id="form_edit_sftpHost"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpHost"
                    >
                        <template #label>
                            {{ $gettext('SFTP Host') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpPort"
                        class="col-md-12 col-lg-6"
                        input-type="number"
                        min="1"
                        step="1"
                        :field="form.sftpPort"
                    >
                        <template #label>
                            {{ $gettext('SFTP Port') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpUsername"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpUsername"
                    >
                        <template #label>
                            {{ $gettext('SFTP Username') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpPassword"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpPassword"
                    >
                        <template #label>
                            {{ $gettext('SFTP Password') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpPrivateKeyPassPhrase"
                        class="col-md-12"
                        :field="form.sftpPrivateKeyPassPhrase"
                    >
                        <template #label>
                            {{ $gettext('SFTP Private Key Pass Phrase') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpPrivateKey"
                        class="col-md-12"
                        input-type="textarea"
                        :field="form.sftpPrivateKey"
                    >
                        <template #label>
                            {{ $gettext('SFTP Private Key') }}
                        </template>
                    </b-wrapped-form-group>
                </div>
            </b-form-group>
        </b-card-body>
    </b-card>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div>
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_edit_adapter" :field="form.adapter">
                    <template #label="{lang}">
                        <translate :key="lang">Storage Adapter</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" v-model="props.field.$model">
                            <b-form-radio value="local">
                                <translate key="lang_form_adapter_local">Local Filesystem</translate>
                            </b-form-radio>
                            <b-form-radio value="s3">
                                <translate key="lang_form_adapter_s3">Remote: S3 Compatible</translate>
                            </b-form-radio>
                            <b-form-radio value="dropbox">
                                <translate key="lang_form_adapter_dropbox">Remote: Dropbox</translate>
                            </b-form-radio>
                            <b-form-radio value="sftp">
                                <translate key="lang_form_adapter_sftp">Remote: SFTP</translate>
                            </b-form-radio>
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_path" :field="form.path">
                    <template #label="{lang}">
                        <translate :key="lang">Path/Suffix</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">For local filesystems, this is the base path of the directory. For remote filesystems, this is the folder prefix.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_storageQuota" :field="form.storageQuota">
                    <template #label="{lang}">
                        <translate :key="lang">Storage Quota</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Set a maximum disk space that this storage location can use. Specify the size with unit, i.e. "8 GB". Units are measured in 1024 bytes. Leave blank to default to the available space on the disk.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>

        <b-card v-show="form.adapter.$model === 's3'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_form_adapter_s3">Remote: S3 Compatible</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-6" id="form_edit_s3CredentialKey"
                                              :field="form.s3CredentialKey">
                            <template #label="{lang}">
                                <translate :key="lang">Access Key ID</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_s3CredentialSecret"
                                              :field="form.s3CredentialSecret">
                            <template #label="{lang}">
                                <translate :key="lang">Secret Key</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_s3Endpoint" :field="form.s3Endpoint">
                            <template #label="{lang}">
                                <translate :key="lang">Endpoint</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_s3Bucket" :field="form.s3Bucket">
                            <template #label="{lang}">
                                <translate :key="lang">Bucket Name</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_s3Region" :field="form.s3Region">
                            <template #label="{lang}">
                                <translate :key="lang">Region</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_s3Version" :field="form.s3Version">
                            <template #label="{lang}">
                                <translate :key="lang">API Version</translate>
                            </template>
                        </b-wrapped-form-group>
                    </b-form-row>
                </b-form-group>
            </b-card-body>
        </b-card>

        <b-card v-show="form.adapter.$model === 'dropbox'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_form_adapter_dropbox">Remote: Dropbox</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-12" id="form_edit_dropboxAuthToken"
                                              :field="form.dropboxAuthToken">
                            <template #label="{lang}">
                                <translate :key="lang">Dropbox Generated Access Token</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">Note: Dropbox now only issues short-lived tokens that will not work for this purpose. If your token begins with "sl", it is short-lived and will not work correctly.</translate>
                            </template>
                        </b-wrapped-form-group>
                    </b-form-row>
                </b-form-group>
            </b-card-body>
        </b-card>
        
        <b-card v-show="form.adapter.$model === 'sftp'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_form_adapter_sftp">Remote: SFTP</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-12 col-lg-6" id="form_edit_sftpHost"
                                              :field="form.sftpHost">
                            <template #label="{lang}">
                                <translate :key="lang">SFTP Host</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-12 col-lg-6" id="form_edit_sftpPort" input-type="number" min="1" step="1"
                                              :field="form.sftpPort">
                            <template #label="{lang}">
                                <translate :key="lang">SFTP Port</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-12 col-lg-6" id="form_edit_sftpUsername"
                                              :field="form.sftpUsername">
                            <template #label="{lang}">
                                <translate :key="lang">SFTP Username</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-12 col-lg-6" id="form_edit_sftpPassword"
                                              :field="form.sftpPassword">
                            <template #label="{lang}">
                                <translate :key="lang">SFTP Password</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-12" id="form_edit_sftpPrivateKeyPassPhrase"
                                              :field="form.sftpPrivateKeyPassPhrase">
                            <template #label="{lang}">
                                <translate :key="lang">SFTP Private Key Pass Phrase</translate>
                            </template>
                        </b-wrapped-form-group>
                        
                        <b-wrapped-form-group class="col-md-12" id="form_edit_sftpPrivateKey" input-type="textarea"
                                              :field="form.sftpPrivateKey">
                            <template #label="{lang}">
                                <translate :key="lang">SFTP Private Key</translate>
                            </template>
                        </b-wrapped-form-group>
                    </b-form-row>
                </b-form-group>
            </b-card-body>
        </b-card>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'StorageLocationForm',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    }
};
</script>

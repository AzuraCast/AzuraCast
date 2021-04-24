<template>
    <div>
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-12" label-for="form_edit_adapter">
                    <template v-slot:label>
                        <translate key="lang_form_edit_adapter">Storage Adapter</translate>
                    </template>

                    <b-form-radio-group stacked id="edit_form_adapter" v-model="form.adapter.$model">
                        <b-form-radio value="local">
                            <translate key="lang_form_adapter_local">Local Filesystem</translate>
                        </b-form-radio>
                        <b-form-radio value="s3">
                            <translate key="lang_form_adapter_s3">Remote: S3 Compatible</translate>
                        </b-form-radio>
                        <b-form-radio value="dropbox">
                            <translate key="lang_form_adapter_dropbox">Remote: Dropbox</translate>
                        </b-form-radio>
                    </b-form-radio-group>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_path">
                    <template v-slot:label>
                        <translate key="lang_form_edit_path">Path/Suffix</translate>
                    </template>
                    <template v-slot:description>
                        <translate key="lang_form_edit_path_desc">For local filesystems, this is the base path of the directory. For remote filesystems, this is the folder prefix.</translate>
                    </template>

                    <b-form-input id="form_edit_path" type="text" v-model="form.path.$model"
                                  :state="form.path.$dirty ? !form.path.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_storageQuota">
                    <template v-slot:label>
                        <translate key="lang_form_edit_storageQuota">Storage Quota</translate>
                    </template>
                    <template v-slot:description>
                        <translate key="lang_form_edit_storageQuota_desc">Set a maximum disk space that this storage location can use. Specify the size with unit, i.e. "8 GB". Units are measured in 1024 bytes. Leave blank to default to the available space on the disk.</translate>
                    </template>

                    <b-form-input id="form_edit_storageQuota" type="text" v-model="form.storageQuota.$model"
                                  :state="form.storageQuota.$dirty ? !form.storageQuota.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>
            </b-row>
        </b-form-group>

        <b-card v-show="form.adapter.$model === 's3'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_form_adapter_s3">Remote: S3 Compatible</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-row>
                        <b-form-group class="col-md-6" label-for="form_edit_s3CredentialKey">
                            <template v-slot:label>
                                <translate key="lang_form_edit_s3CredentialKey">Access Key ID</translate>
                            </template>

                            <b-form-input id="form_edit_s3CredentialKey" type="text" v-model="form.s3CredentialKey.$model"
                                          :state="form.s3CredentialKey.$dirty ? !form.s3CredentialKey.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="form_edit_s3CredentialSecret">
                            <template v-slot:label>
                                <translate key="lang_form_edit_s3CredentialSecret">Secret Key</translate>
                            </template>

                            <b-form-input id="form_edit_s3CredentialSecret" type="text" v-model="form.s3CredentialSecret.$model"
                                          :state="form.s3CredentialSecret.$dirty ? !form.s3CredentialSecret.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="form_edit_s3Endpoint">
                            <template v-slot:label>
                                <translate key="lang_form_edit_s3Endpoint">Endpoint</translate>
                            </template>

                            <b-form-input id="form_edit_s3Endpoint" type="text" v-model="form.s3Endpoint.$model"
                                          :state="form.s3Endpoint.$dirty ? !form.s3Endpoint.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="form_edit_s3Bucket">
                            <template v-slot:label>
                                <translate key="lang_form_edit_s3Bucket">Bucket Name</translate>
                            </template>

                            <b-form-input id="form_edit_s3Bucket" type="text" v-model="form.s3Bucket.$model"
                                          :state="form.s3Bucket.$dirty ? !form.s3Bucket.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="form_edit_s3Region">
                            <template v-slot:label>
                                <translate key="lang_form_edit_s3Region">Region</translate>
                            </template>

                            <b-form-input id="form_edit_s3Region" type="text" v-model="form.s3Region.$model"
                                          :state="form.s3Region.$dirty ? !form.s3Region.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="form_edit_s3Version">
                            <template v-slot:label>
                                <translate key="lang_form_edit_s3Version">API Version</translate>
                            </template>

                            <b-form-input id="form_edit_s3Version" type="text" v-model="form.s3Version.$model"
                                          :state="form.s3Version.$dirty ? !form.s3Version.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </b-row>
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
                    <b-row>
                        <b-form-group class="col-md-12" label-for="form_edit_dropboxAuthToken">
                            <template v-slot:label>
                                <translate key="lang_form_edit_dropboxAuthToken">Dropbox Generated Access Token</translate>
                            </template>
                            <template v-slot:description>
                                <a href="https://dropbox.tech/developers/generate-an-access-token-for-your-own-account" target="_blank" v-translate key="lang_form_edit_dropboxAuthToken_desc">Learn More about Dropbox Auth Tokens</a>
                            </template>

                            <b-form-input id="form_edit_dropboxAuthToken" type="text" v-model="form.dropboxAuthToken.$model"
                                          :state="form.dropboxAuthToken.$dirty ? !form.dropboxAuthToken.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </b-row>
                </b-form-group>
            </b-card-body>
        </b-card>
    </div>
</template>

<script>
export default {
    name: 'StorageLocationForm',
    props: {
        form: Object
    }
};
</script>

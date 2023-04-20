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

    <s3
        v-if="form.adapter.$model === 's3'"
        :form="form"
    />

    <dropbox
        v-if="form.adapter.$model === 'dropbox'"
        :form="form"
    />

    <sftp
        v-if="form.adapter.$model === 'sftp'"
        :form="form"
    />
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import Dropbox from "./Form/Dropbox.vue";
import S3 from "./Form/S3.vue";
import Sftp from "./Form/Sftp.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>

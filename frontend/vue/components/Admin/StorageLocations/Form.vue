<template>
    <div class="row g-3 mb-3">
        <form-group-multi-check
            id="form_edit_adapter"
            class="col-md-12"
            :field="form.adapter"
            :options="adapterOptions"
            stacked
            radio
            :label="$gettext('Storage Adapter')"
        />

        <form-group-field
            id="form_edit_path"
            class="col-md-12"
            :field="form.path"
            :label="$gettext('Path/Suffix')"
            :description="$gettext('For local filesystems, this is the base path of the directory. For remote filesystems, this is the folder prefix.')"
        />

        <form-group-field
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
        </form-group-field>
    </div>

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
import FormGroupField from "~/components/Form/FormGroupField.vue";
import Dropbox from "./Form/Dropbox.vue";
import S3 from "./Form/S3.vue";
import Sftp from "./Form/Sftp.vue";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const adapterOptions = computed(() => {
    return [
        {
            value: 'local',
            text: $gettext('Local Filesystem')
        },
        {
            value: 's3',
            text: $gettext('Remote: S3 Compatible')
        },
        {
            value: 'dropbox',
            text: $gettext('Remote: Dropbox')
        },
        {
            value: 'sftp',
            text: $gettext('Remote: SFTP')
        }
    ];
});

</script>

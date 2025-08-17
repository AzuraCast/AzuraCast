<template>
    <tab
        :label="$gettext('Administration')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-checkbox
                id="edit_form_is_enabled"
                class="col-md-6"
                :field="v$.is_enabled"
                :label="$gettext('Enable Broadcasting')"
                :description="$gettext('If disabled, the station will not broadcast or shuffle its AutoDJ.')"
            />

            <form-group-field
                id="edit_form_radio_base_dir"
                class="col-md-6"
                :field="v$.radio_base_dir"
                advanced
                :label="$gettext('Base Station Directory')"
                :description="$gettext('The parent directory where station playlist and configuration files are stored. Leave blank to use default directory.')"
            />
        </div>

        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_max_bitrate"
                class="col-md-4"
                :field="v$.max_bitrate"
                :label="$gettext('Maximum Bitrate')"
                :description="$gettext('The maximum bitrate in which the station allowed to broadcast at, in Kbps. 0 for unlimited.')"
                input-number
            />

            <form-group-field
                id="edit_form_max_mounts"
                class="col-md-4"
                :field="v$.max_mounts"
                :label="$gettext('Maximum Mounts')"
                :description="$gettext('The maximum number of mount points allowed. 0 for unlimited.')"
                input-number
            />

            <form-group-field
                id="edit_form_max_hls_streams"
                class="col-md-4"
                :field="v$.max_hls_streams"
                :label="$gettext('Maximum HLS Streams')"
                :description="$gettext('The maximum number of HLS streams allowed. 0 for unlimited.')"
                input-number
            />
        </div>

        <loading :loading="storageLocationsLoading">
            <div class="row g-3">
                <form-group-select
                    id="edit_form_media_storage_location"
                    class="col-md-12"
                    :field="v$.media_storage_location"
                    :options="storageLocationOptions.media_storage_location"
                    :label="$gettext('Media Storage Location')"
                />

                <form-group-select
                    id="edit_form_recordings_storage_location"
                    class="col-md-12"
                    :field="v$.recordings_storage_location"
                    :options="storageLocationOptions.recordings_storage_location"
                    :label="$gettext('Live Recordings Storage Location')"
                />

                <form-group-select
                    id="edit_form_podcasts_storage_location"
                    class="col-md-12"
                    :field="v$.podcasts_storage_location"
                    :options="storageLocationOptions.podcasts_storage_location"
                    :label="$gettext('Podcasts Storage Location')"
                />
            </div>
        </loading>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {onMounted, reactive, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useValidatedFormTab} from "~/functions/useValidatedFormTab.ts";
import Tab from "~/components/Common/Tab.vue";
import {getApiUrl} from "~/router";
import {ApiFormSimpleOptions, ApiGenericForm} from "~/entities/ApiInterfaces.ts";
import {useTranslate} from "~/vendor/gettext.ts";

const props = defineProps<{
    isEditMode: boolean,
}>();

const form = defineModel<ApiGenericForm>('form', {required: true});

const storageLocationApiUrl = getApiUrl('/admin/stations/storage-locations');

const {v$, tabClass} = useValidatedFormTab(
    form,
    {
        is_enabled: {},
        media_storage_location: {},
        recordings_storage_location: {},
        podcasts_storage_location: {},
        max_bitrate: {},
        max_mounts: {},
        max_hls_streams: {},
        radio_base_dir: {},
    },
    {
        media_storage_location: '',
        recordings_storage_location: '',
        podcasts_storage_location: '',
        is_enabled: true,
        max_bitrate: 0,
        max_mounts: 0,
        max_hls_streams: 0,
        radio_base_dir: '',
    }
);

interface StorageLocationOptions {
    media_storage_location: ApiFormSimpleOptions,
    recordings_storage_location: ApiFormSimpleOptions,
    podcasts_storage_location: ApiFormSimpleOptions
}

const storageLocationsLoading = ref<boolean>(true);
const storageLocationOptions = reactive<StorageLocationOptions>({
    media_storage_location: [],
    recordings_storage_location: [],
    podcasts_storage_location: []
});

const {$gettext} = useTranslate();

const langNewStorageLocation = $gettext("Create a new storage location based on the base directory.");

const filterLocations = (group: ApiFormSimpleOptions): ApiFormSimpleOptions => {
    if (props.isEditMode) {
        return group;
    }

    const newGroup = group.slice();
    newGroup.push({
        value: "",
        text: langNewStorageLocation
    });
    return newGroup;
}

const {axios} = useAxios();

const loadLocations = async () => {
    try {
        const {data} = await axios.get<StorageLocationOptions>(storageLocationApiUrl.value);

        storageLocationOptions.media_storage_location = filterLocations(
            data.media_storage_location
        );
        storageLocationOptions.recordings_storage_location = filterLocations(
            data.recordings_storage_location
        );
        storageLocationOptions.podcasts_storage_location = filterLocations(
            data.podcasts_storage_location
        );
    } finally {
        storageLocationsLoading.value = false;
    }
};

onMounted(loadLocations);
</script>

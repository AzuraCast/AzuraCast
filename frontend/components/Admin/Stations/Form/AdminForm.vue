<template>
    <tab
        :label="$gettext('Administration')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-checkbox
                id="edit_form_is_enabled"
                class="col-md-6"
                :field="r$.is_enabled"
                :label="$gettext('Enable Broadcasting')"
                :description="$gettext('If disabled, the station will not broadcast or shuffle its AutoDJ.')"
            />

            <form-group-field
                id="edit_form_radio_base_dir"
                class="col-md-6"
                :field="r$.radio_base_dir"
                advanced
                :label="$gettext('Base Station Directory')"
                :description="$gettext('The parent directory where station playlist and configuration files are stored. Leave blank to use default directory.')"
            />
        </div>

        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_max_bitrate"
                class="col-md-4"
                :field="r$.max_bitrate"
                :label="$gettext('Maximum Bitrate')"
                :description="$gettext('The maximum bitrate in which the station allowed to broadcast at, in Kbps. 0 for unlimited.')"
                input-number
            />

            <form-group-field
                id="edit_form_max_mounts"
                class="col-md-4"
                :field="r$.max_mounts"
                :label="$gettext('Maximum Mounts')"
                :description="$gettext('The maximum number of mount points allowed. 0 for unlimited.')"
                input-number
            />

            <form-group-field
                id="edit_form_max_hls_streams"
                class="col-md-4"
                :field="r$.max_hls_streams"
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
                    :field="r$.media_storage_location"
                    :options="storageLocationOptions.media_storage_location"
                    :label="$gettext('Media Storage Location')"
                />

                <form-group-select
                    id="edit_form_recordings_storage_location"
                    class="col-md-12"
                    :field="r$.recordings_storage_location"
                    :options="storageLocationOptions.recordings_storage_location"
                    :label="$gettext('Live Recordings Storage Location')"
                />

                <form-group-select
                    id="edit_form_podcasts_storage_location"
                    class="col-md-12"
                    :field="r$.podcasts_storage_location"
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
import {computed, onMounted, reactive, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import Tab from "~/components/Common/Tab.vue";
import {ApiFormSimpleOptions} from "~/entities/ApiInterfaces.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {storeToRefs} from "pinia";
import {useAdminStationsForm} from "~/components/Admin/Stations/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const props = defineProps<{
    isEditMode: boolean,
}>();

const {getApiUrl} = useApiRouter();
const storageLocationApiUrl = getApiUrl('/admin/stations/storage-locations');

const {r$} = storeToRefs(useAdminStationsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.adminTab));

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

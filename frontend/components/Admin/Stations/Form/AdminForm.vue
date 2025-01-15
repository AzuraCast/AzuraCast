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
                v-if="enableAdvancedFeatures"
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
            />

            <form-group-field
                id="edit_form_max_mounts"
                class="col-md-4"
                :field="v$.max_mounts"
                :label="$gettext('Maximum Mounts')"
                :description="$gettext('The maximum number of mount points allowed. 0 for unlimited.')"
            />

            <form-group-field
                id="edit_form_max_hls_streams"
                class="col-md-4"
                :field="v$.max_hls_streams"
                :label="$gettext('Maximum Hls Streams')"
                :description="$gettext('The maximum number of HLS streams allowed. 0 for unlimited.')"
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
import {computed, onMounted, reactive, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {useAzuraCast} from "~/vendor/azuracast";
import Tab from "~/components/Common/Tab.vue";
import {getApiUrl} from "~/router";

interface StationsAdminFormProps extends FormTabProps {
    isEditMode: boolean,
}

const props = defineProps<StationsAdminFormProps>();
const emit = defineEmits<FormTabEmits>();

const storageLocationApiUrl = getApiUrl('/admin/stations/storage-locations');

const {enableAdvancedFeatures} = useAzuraCast();

const {v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    computed(() => {
        let validations: {
            [key: string | number]: any
        } = {
            is_enabled: {},
            media_storage_location: {},
            recordings_storage_location: {},
            podcasts_storage_location: {},
            max_bitrate: {},
            max_mounts: {},
            max_hls_streams: {}
        };

        if (enableAdvancedFeatures) {
            validations = {
                ...validations,
                radio_base_dir: {},
            };
        }

        return validations;
    }),
    () => {
        let blankForm: {
            [key: string]: any
        } = {
            media_storage_location: '',
            recordings_storage_location: '',
            podcasts_storage_location: '',
            is_enabled: true,
            max_bitrate: 0,
            max_mounts: 0,
            max_hls_streams: 0
        };

        if (enableAdvancedFeatures) {
            blankForm = {
                ...blankForm,
                radio_base_dir: '',
            };
        }

        return blankForm;
    }
);

const storageLocationsLoading = ref(true);
const storageLocationOptions = reactive({
    media_storage_location: {},
    recordings_storage_location: {},
    podcasts_storage_location: {}
});

const filterLocations = (group) => {
    if (!props.isEditMode) {
        return group;
    }

    const newGroup = {};
    for (const oldKey in group) {
        if (oldKey !== "") {
            newGroup[oldKey] = group[oldKey];
        }
    }
    return newGroup;
}

const {axios} = useAxios();

const loadLocations = () => {
    axios.get(storageLocationApiUrl.value).then((resp) => {
        storageLocationOptions.media_storage_location = filterLocations(
            resp.data.media_storage_location
        );
        storageLocationOptions.recordings_storage_location = filterLocations(
            resp.data.recordings_storage_location
        );
        storageLocationOptions.podcasts_storage_location = filterLocations(
            resp.data.podcasts_storage_location
        );
    }).finally(() => {
        storageLocationsLoading.value = false;
    });
};

onMounted(loadLocations);
</script>

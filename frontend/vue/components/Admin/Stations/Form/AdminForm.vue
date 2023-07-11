<template>
    <o-tab-item
        v-if="showAdminTab"
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
                v-if="showAdvanced"
                id="edit_form_radio_base_dir"
                class="col-md-6"
                :field="v$.radio_base_dir"
                advanced
                :label="$gettext('Base Station Directory')"
                :description="$gettext('The parent directory where station playlist and configuration files are stored. Leave blank to use default directory.')"
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
    </o-tab-item>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import objectToFormOptions from "~/functions/objectToFormOptions";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed, onMounted, reactive, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    isEditMode: {
        type: Boolean,
        required: true
    },
    storageLocationApiUrl: {
        type: String,
        required: true
    },
    showAdvanced: {
        type: Boolean,
        default: true
    },
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    computed(() => {
        let validations = {
            media_storage_location: {},
            recordings_storage_location: {},
            podcasts_storage_location: {},
            is_enabled: {},
        };

        if (props.showAdvanced) {
            validations = {
                ...validations,
                radio_base_dir: {},
            };
        }

        return validations;
    }),
    form
);

const storageLocationsLoading = ref(true);
const storageLocationOptions = reactive({
    media_storage_location: [],
    recordings_storage_location: [],
    podcasts_storage_location: []
});

const filterLocations = (group) => {
    if (!props.isEditMode) {
        return group;
    }

    let newGroup = {};
    for (const oldKey in group) {
        if (oldKey !== "") {
            newGroup[oldKey] = group[oldKey];
        }
    }
    return newGroup;
}

const {axios} = useAxios();

const loadLocations = () => {
    axios.get(props.storageLocationApiUrl).then((resp) => {
        storageLocationOptions.media_storage_location = objectToFormOptions(
            filterLocations(resp.data.media_storage_location)
        );
        storageLocationOptions.recordings_storage_location = objectToFormOptions(
            filterLocations(resp.data.recordings_storage_location)
        );
        storageLocationOptions.podcasts_storage_location = objectToFormOptions(
            filterLocations(resp.data.podcasts_storage_location)
        );
    }).finally(() => {
        storageLocationsLoading.value = false;
    });
};

onMounted(loadLocations);
</script>

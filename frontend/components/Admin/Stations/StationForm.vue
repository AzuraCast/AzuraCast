<template>
    <loading :loading="isLoading">
        <div
            v-show="error != null"
            class="alert alert-danger"
        >
            {{ error }}
        </div>

        <form
            class="form vue-form"
            @submit.prevent="submit"
        >
            <tabs content-class="mt-3">
                <admin-stations-profile-form
                    v-model:form="form"
                    :timezones="timezones"
                />

                <admin-stations-frontend-form
                    v-model:form="form"
                    :is-shoutcast-installed="isShoutcastInstalled"
                    :countries="countries"
                />

                <admin-stations-backend-form
                    v-model:form="form"
                    :station="station"
                    :is-stereo-tool-installed="isStereoToolInstalled"
                />

                <admin-stations-hls-form
                    v-model:form="form"
                    :station="station"
                />

                <admin-stations-requests-form
                    v-model:form="form"
                    :station="station"
                />

                <admin-stations-streamers-form
                    v-model:form="form"
                    :station="station"
                />

                <admin-stations-admin-form
                    v-if="showAdminTab"
                    v-model:form="form"
                    :is-edit-mode="isEditMode"
                />
            </tabs>

            <slot name="submitButton">
                <div class="buttons mt-3">
                    <button
                        type="submit"
                        class="btn btn-lg"
                        :class="(!isValid) ? 'btn-danger' : 'btn-primary'"
                    >
                        <slot name="submitButtonText">
                            {{ $gettext('Save Changes') }}
                        </slot>
                    </button>
                </div>
            </slot>
        </form>
    </loading>
</template>

<script setup lang="ts">
import AdminStationsProfileForm from "./Form/ProfileForm.vue";
import AdminStationsFrontendForm from "./Form/FrontendForm.vue";
import AdminStationsBackendForm from "./Form/BackendForm.vue";
import AdminStationsAdminForm from "./Form/AdminForm.vue";
import AdminStationsHlsForm from "./Form/HlsForm.vue";
import AdminStationsRequestsForm from "./Form/RequestsForm.vue";
import AdminStationsStreamersForm from "./Form/StreamersForm.vue";
import {computed, nextTick, ref, watch} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import stationFormProps from "~/components/Admin/Stations/stationFormProps";
import {useResettableRef} from "~/functions/useResettableRef";
import Loading from '~/components/Common/Loading.vue';
import Tabs from "~/components/Common/Tabs.vue";
import {GlobalPermission, userAllowed} from "~/acl";

const props = defineProps({
    ...stationFormProps,
    createUrl: {
        type: String,
        default: null
    },
    editUrl: {
        type: String,
        default: null
    },
    isEditMode: {
        type: Boolean,
        required: true
    },
    isModal: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['error', 'submitted', 'loadingUpdate', 'validUpdate']);

const showAdminTab = userAllowed(GlobalPermission.Stations);

const {form, resetForm, v$, ifValid} = useVuelidateOnForm();

const isValid = computed(() => {
    return !v$.value?.$invalid;
});

watch(isValid, (newValue) => {
    emit('validUpdate', newValue);
});

const isLoading = ref(true);

watch(isLoading, (newValue) => {
    emit('loadingUpdate', newValue);
});

const error = ref(null);

const blankStation = {
    stereo_tool_configuration_file_path: null,
    links: {
        stereo_tool_configuration: null
    }
};

const {record: station, reset: resetStation} = useResettableRef(blankStation);

const clear = () => {
    resetForm();
    resetStation();

    isLoading.value = false;
    error.value = null;
};

const populateForm = (data) => {
    form.value = mergeExisting(form.value, data);
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doLoad = () => {
    isLoading.value = true;

    axios.get(props.editUrl).then((resp) => {
        populateForm(resp.data);
    }).catch((err) => {
        emit('error', err);
    }).finally(() => {
        isLoading.value = false;
    });
};

const reset = () => {
    nextTick(() => {
        clear();
        if (props.isEditMode) {
            doLoad();
        }
    });
};

const submit = () => {
    ifValid(() => {
        error.value = null;

        axios({
            method: (props.isEditMode)
                ? 'PUT'
                : 'POST',
            url: (props.isEditMode)
                ? props.editUrl
                : props.createUrl,
            data: form.value
        }).then(() => {
            notifySuccess();
            emit('submitted');
        }).catch((err) => {
            error.value = err.response.data.message;
        });
    });
};

defineExpose({
    reset,
    submit
});
</script>

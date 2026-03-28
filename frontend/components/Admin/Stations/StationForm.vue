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
                    :timezones="timezones"
                />

                <admin-stations-frontend-form
                    :is-rsas-installed="isRsasInstalled"
                    :is-shoutcast-installed="isShoutcastInstalled"
                    :countries="countries"
                />

                <admin-stations-backend-form
                    :is-stereo-tool-installed="isStereoToolInstalled"
                />

                <admin-stations-hls-form/>

                <admin-stations-requests-form/>

                <admin-stations-streamers-form/>

                <admin-stations-admin-form
                    v-if="showAdminTab"
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
import AdminStationsProfileForm from "~/components/Admin/Stations/Form/ProfileForm.vue";
import AdminStationsFrontendForm from "~/components/Admin/Stations/Form/FrontendForm.vue";
import AdminStationsBackendForm from "~/components/Admin/Stations/Form/BackendForm.vue";
import AdminStationsAdminForm from "~/components/Admin/Stations/Form/AdminForm.vue";
import AdminStationsHlsForm from "~/components/Admin/Stations/Form/HlsForm.vue";
import AdminStationsRequestsForm from "~/components/Admin/Stations/Form/RequestsForm.vue";
import AdminStationsStreamersForm from "~/components/Admin/Stations/Form/StreamersForm.vue";
import {computed, nextTick, onMounted, ref, watch} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {getErrorAsString, useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import Loading from "~/components/Common/Loading.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {ApiAdminVueStationsFormProps, GlobalPermissions} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {useAdminStationsForm} from "~/components/Admin/Stations/Form/form.ts";
import {useUserAllowed} from "~/functions/useUserAllowed.ts";

defineOptions({
    inheritAttrs: false
});

interface StationFormProps extends ApiAdminVueStationsFormProps {
    createUrl?: string,
    editUrl?: string | null,
    isEditMode: boolean,
    isModal?: boolean
}

const props = withDefaults(
    defineProps<StationFormProps>(),
    {
        isRsasInstalled: false,
        isShoutcastInstalled: false,
        isStereoToolInstalled: false,
        editUrl: null,
        isModal: false
    }
);

const emit = defineEmits<{
    (e: 'submitted'): void,
    (e: 'loadingUpdate', loading: boolean): void,
    (e: 'validUpdate', valid: boolean): void
}>();

const {userAllowed} = useUserAllowed();
const showAdminTab = userAllowed(GlobalPermissions.Stations);

const formStore = useAdminStationsForm();
const {form, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

const isValid = computed(() => {
    return !r$.value?.$invalid;
});

watch(isValid, (newValue) => {
    emit('validUpdate', newValue);
});

const isLoading = ref(true);

watch(isLoading, (newValue) => {
    emit('loadingUpdate', newValue);
});

const error = ref<string | null>(null);

const clear = () => {
    resetForm();

    isLoading.value = false;
    error.value = null;
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doLoad = async () => {
    if (!props.editUrl) {
        return;
    }

    isLoading.value = true;

    try {
        const {data} = await axios.get(props.editUrl);
        form.value = mergeExisting(form.value, data);
    } finally {
        isLoading.value = false;
    }
};

const reset = async () => {
    await nextTick();

    clear();
    if (props.isEditMode) {
        void doLoad();
    }
};

onMounted(() => {
    if (!props.isModal) {
        void reset();
    }
});

const submit = async () => {
    const apiUrl = (props.isEditMode) ? props.editUrl : props.createUrl;

    const {valid} = await r$.value.$validate();
    if (!valid || !apiUrl) {
        return;
    }

    error.value = null;

    try {
        await axios({
            method: (props.isEditMode)
                ? 'PUT'
                : 'POST',
            url: apiUrl,
            data: form.value
        });

        notifySuccess();
        emit('submitted');
    } catch (e) {
        error.value = getErrorAsString(e);
    }
};

defineExpose({
    reset,
    submit
});
</script>

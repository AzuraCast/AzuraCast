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
                    :is-rsas-installed="isRsasInstalled"
                    :is-shoutcast-installed="isShoutcastInstalled"
                    :countries="countries"
                />

                <admin-stations-backend-form
                    v-model:form="form"
                    :is-stereo-tool-installed="isStereoToolInstalled"
                />

                <admin-stations-hls-form
                    v-model:form="form"
                />

                <admin-stations-requests-form
                    v-model:form="form"
                />

                <admin-stations-streamers-form
                    v-model:form="form"
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

<script lang="ts">
export interface StationFormParentProps {
    // Profile
    timezones: Record<string, string>,
    // Frontend
    isRsasInstalled?: boolean,
    isShoutcastInstalled?: boolean,
    isStereoToolInstalled?: boolean,
    countries: Record<string, string>
}
</script>

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
import Loading from '~/components/Common/Loading.vue';
import Tabs from "~/components/Common/Tabs.vue";
import {GlobalPermission, userAllowed} from "~/acl";

defineOptions({
    inheritAttrs: false
});

interface StationFormProps extends StationFormParentProps {
    createUrl?: string,
    editUrl?: string,
    isEditMode: boolean,
    isModal?: boolean
}

const props = withDefaults(
    defineProps<StationFormProps>(),
    {
        isRsasInstalled: false,
        isShoutcastInstalled: false,
        isStereoToolInstalled: false,
        isModal: false
    }
);

const emit = defineEmits<{
    (e: 'error', error: string): void,
    (e: 'submitted'): void,
    (e: 'loadingUpdate', loading: boolean): void,
    (e: 'validUpdate', valid: boolean): void
}>();

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

const clear = () => {
    resetForm();

    isLoading.value = false;
    error.value = null;
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doLoad = () => {
    isLoading.value = true;

    axios.get(props.editUrl).then(({data}) => {
        form.value = mergeExisting(form.value, data);
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

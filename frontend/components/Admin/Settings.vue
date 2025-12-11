<template>
    <form
        class="form vue-form"
        @submit.prevent="submit"
    >
        <slot name="preCard" />

        <section
            class="card"
            role="region"
            aria-labelledby="hdr_system_settings"
        >
            <div class="card-header text-bg-primary">
                <h2
                    id="hdr_system_settings"
                    class="card-title"
                >
                    <slot name="cardTitle">
                        {{ $gettext('System Settings') }}
                    </slot>
                </h2>
            </div>

            <slot name="cardUpper" />

            <div
                v-show="error != null"
                class="alert alert-danger"
            >
                {{ error }}
            </div>

            <div class="card-body">
                <loading :loading="loading || formLoading || dataLoading" lazy>
                    <tabs v-if="data" content-class="mt-3">
                        <settings-general-tab/>
                        <settings-security-privacy-tab/>
                        <settings-services-tab
                            :release-channel="data.releaseChannel"
                        />
                        <settings-debugging-tab/>
                    </tabs>

                    <button
                        type="submit"
                        class="btn mt-3 btn-lg"
                        :class="(r$.$invalid) ? 'btn-danger' : 'btn-primary'"
                    >
                        <slot name="submitButtonName">
                            {{ $gettext('Save Changes') }}
                        </slot>
                    </button>
                </loading>
            </div>
        </section>
    </form>
</template>

<script setup lang="ts">
import SettingsGeneralTab from "~/components/Admin/Settings/GeneralTab.vue";
import SettingsServicesTab from "~/components/Admin/Settings/ServicesTab.vue";
import SettingsSecurityPrivacyTab from "~/components/Admin/Settings/SecurityPrivacyTab.vue";
import SettingsDebuggingTab from "~/components/Admin/Settings/DebuggingTab.vue";
import {onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import Loading from "~/components/Common/Loading.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {useAdminSettingsForm} from "~/components/Admin/Settings/form.ts";
import {storeToRefs} from "pinia";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys} from "~/entities/Queries.ts";
import {ApiAdminVueSettingsProps} from "~/entities/ApiInterfaces.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

defineOptions({
    inheritAttrs: false
});

withDefaults(
    defineProps<{
        loading?: boolean
    }>(),
    {
        loading: false
    }
);

const emit = defineEmits<{
    (e: 'saved'): void
}>();

const {getApiUrl} = useApiRouter();
const apiUrl = getApiUrl('/admin/settings/general');
const propsUrl = getApiUrl('/admin/vue/settings');

const formStore = useAdminSettingsForm();
const {form, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

const formLoading = ref(true);
const error = ref(null);

const {axios} = useAxios();

const {data, isLoading: dataLoading} = useQuery<ApiAdminVueSettingsProps>({
    queryKey: [QueryKeys.AdminSettings, 'props'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminVueSettingsProps>(propsUrl.value, {signal});
        return data;
    },
    placeholderData: () => ({
        releaseChannel: 'rolling'
    })
});

const populateForm = (data: typeof form.value) => {
    resetForm();
    form.value = mergeExisting(form.value, data);
};

const relist = async () => {
    resetForm();
    formLoading.value = true;

    const {data} = await axios.get(apiUrl.value);

    populateForm(data);
    formLoading.value = false;
};

onMounted(relist);

const {notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const submit = async () => {
    const {valid} = await r$.value.$validate();
    if (!valid) {
        return;
    }

    await axios({
        method: 'PUT',
        url: apiUrl.value,
        data: form.value
    });

    notifySuccess($gettext('Changes saved.'));
    emit('saved');
}
</script>

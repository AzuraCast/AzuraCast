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
                <loading :loading="isLoading">
                    <tabs content-class="mt-3">
                        <settings-general-tab/>
                        <settings-security-privacy-tab/>
                        <settings-services-tab
                            :release-channel="releaseChannel"
                        />
                        <settings-debugging-tab/>
                    </tabs>
                </loading>
            </div>

            <div class="card-body">
                <button
                    type="submit"
                    class="btn btn-lg"
                    :class="(r$.$invalid) ? 'btn-danger' : 'btn-primary'"
                >
                    <slot name="submitButtonName">
                        {{ $gettext('Save Changes') }}
                    </slot>
                </button>
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
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import Loading from "~/components/Common/Loading.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {useAdminSettingsForm} from "~/components/Admin/Settings/form.ts";
import {storeToRefs} from "pinia";
import {getApiUrl} from "~/router.ts";

defineOptions({
    inheritAttrs: false
});

export interface SettingsProps {
    releaseChannel?: string
}

withDefaults(
    defineProps<SettingsProps>(),
    {
        releaseChannel: 'rolling'
    }
);

const emit = defineEmits<{
    (e: 'saved'): void
}>();

const apiUrl = getApiUrl('/admin/settings/general');

const formStore = useAdminSettingsForm();
const {form, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

const isLoading = ref(true);
const error = ref(null);

const {axios} = useAxios();

const populateForm = (data: typeof form.value) => {
    resetForm();
    form.value = mergeExisting(form.value, data);
};

const relist = () => {
    resetForm();
    isLoading.value = true;

    void axios.get(apiUrl.value).then((resp) => {
        populateForm(resp.data);
        isLoading.value = false;
    });
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

    emit('saved');
    notifySuccess($gettext('Changes saved.'));
    relist();
}
</script>

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
                        <settings-general-tab v-model:form="form" />
                        <settings-security-privacy-tab v-model:form="form" />
                        <settings-services-tab
                            v-model:form="form"
                            :release-channel="releaseChannel"
                            :test-message-url="testMessageUrl"
                            :acme-url="acmeUrl"
                        />
                    </tabs>
                </loading>
            </div>

            <div class="card-body">
                <button
                    type="submit"
                    class="btn btn-lg"
                    :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                >
                    <slot name="submitButtonName">
                        {{ $gettext('Save Changes') }}
                    </slot>
                </button>
            </div>
        </section>
    </form>
</template>

<script setup>
import SettingsGeneralTab from "./Settings/GeneralTab.vue";
import SettingsServicesTab from "./Settings/ServicesTab.vue";
import SettingsSecurityPrivacyTab from "~/components/Admin/Settings/SecurityPrivacyTab.vue";
import {onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import Loading from "~/components/Common/Loading.vue";
import settingsProps from "~/components/Admin/settingsProps";
import Tabs from "~/components/Common/Tabs.vue";

const props = defineProps({
    ...settingsProps
});

const emit = defineEmits(['saved']);

const {form, resetForm, v$, ifValid} = useVuelidateOnForm();

const isLoading = ref(true);
const error = ref(null);

const {axios} = useAxios();

const populateForm = (data) => {
    resetForm();
    form.value = mergeExisting(form.value, data);
};

const relist = () => {
    resetForm();
    isLoading.value = true;

    axios.get(props.apiUrl).then((resp) => {
        populateForm(resp.data);
        isLoading.value = false;
    });
};

onMounted(relist);

const {wrapWithLoading, notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const submit = () => {
    ifValid(() => {
        wrapWithLoading(
            axios({
                method: 'PUT',
                url: props.apiUrl,
                data: form.value
            })
        ).then(() => {
            emit('saved');

            notifySuccess($gettext('Changes saved.'));
            relist();
        });
    });
}
</script>

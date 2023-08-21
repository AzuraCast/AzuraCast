<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <type-select
            v-if="!type"
            :type-details="typeDetails"
            @select="setType"
        />

        <tabs v-else>
            <basic-info
                v-model:form="form"
                :trigger-details="triggerDetails"
                :triggers="triggers"
            />

            <component
                :is="formComponent"
                v-model:form="form"
                :label="typeTitle"
            />
        </tabs>
    </modal-form>
</template>

<script setup>
import TypeSelect from "./Form/TypeSelect";
import BasicInfo from "./Form/BasicInfo";
import {get, map} from "lodash";
import Generic from "./Form/Generic";
import Email from "./Form/Email";
import Tunein from "./Form/Tunein";
import Discord from "./Form/Discord";
import Telegram from "./Form/Telegram";
import Twitter from "./Form/Twitter";
import GoogleAnalyticsV3 from "./Form/GoogleAnalyticsV3";
import GoogleAnalyticsV4 from "./Form/GoogleAnalyticsV4";
import MatomoAnalytics from "./Form/MatomoAnalytics";
import Mastodon from "./Form/Mastodon";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, nextTick, provide, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import {getTriggers, WebhookType} from "~/components/Entity/Webhooks";
import Tabs from "~/components/Common/Tabs.vue";
import RadioDe from "~/components/Stations/Webhooks/Form/RadioDe.vue";

const props = defineProps({
    ...baseEditModalProps,
    nowPlayingUrl: {
        type: String,
        required: true
    },
    typeDetails: {
        type: Object,
        required: true
    },
    triggerDetails: {
        type: Object,
        required: true
    }
});

provide('nowPlayingUrl', props.nowPlayingUrl);

const emit = defineEmits(['relist']);

const type = ref(null);

const $modal = ref(); // Template Ref

const webhookComponents = {
    [WebhookType.Generic]: Generic,
    [WebhookType.Email]: Email,
    [WebhookType.TuneIn]: Tunein,
    [WebhookType.RadioDe]: RadioDe,
    [WebhookType.Discord]: Discord,
    [WebhookType.Telegram]: Telegram,
    [WebhookType.Twitter]: Twitter,
    [WebhookType.Mastodon]: Mastodon,
    [WebhookType.GoogleAnalyticsV3]: GoogleAnalyticsV3,
    [WebhookType.GoogleAnalyticsV4]: GoogleAnalyticsV4,
    [WebhookType.MatomoAnalytics]: MatomoAnalytics,
};

const triggers = computed(() => {
    if (!type.value) {
        return [];
    }

    return map(
        getTriggers(type.value),
        (trigger) => {
            return {
                key: trigger,
                title: get(props.triggerDetails, [trigger, 'title']),
                description: get(props.triggerDetails, [trigger, 'description'])
            };
        }
    );
});

const typeTitle = computed(() => {
    return get(props.typeDetails, [type.value, 'title'], '');
});

const formComponent = computed(() => {
    return get(webhookComponents, type.value, Generic);
});

const {
    loading,
    error,
    isEditMode,
    form,
    v$,
    resetForm,
    clearContents: originalClearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    props,
    emit,
    $modal,
    {},
    {},
    {
        populateForm: (data, formRef) => {
            type.value = data.type;
            formRef.value = {
                name: data.name,
                triggers: data.triggers,
                config: data.config
            };
        },
        getSubmittableFormData(formRef, isEditModeRef) {
            const formData = formRef.value;
            if (!isEditModeRef.value) {
                formData.type = type.value;
            }
            return formData;
        },
    }
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    if (isEditMode.value) {
        return $gettext('Edit Web Hook');
    }

    return type.value
        ? $gettext('Add Web Hook')
        : $gettext('Select Web Hook Type');
});

const clearContents = () => {
    type.value = null;
    originalClearContents();
};

const setType = (newType) => {
    type.value = newType;
    nextTick(resetForm);
};

defineExpose({
    create,
    edit,
    close
});
</script>

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
                :type="type"
                :trigger-details="triggerDetails"
            />

            <component
                :is="formComponent"
                v-model:form="form"
                :title="typeTitle"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import TypeSelect from "~/components/Stations/Webhooks/Form/TypeSelect.vue";
import BasicInfo from "~/components/Stations/Webhooks/Form/BasicInfo.vue";
import {get} from "lodash";
import Generic from "~/components/Stations/Webhooks/Form/Generic.vue";
import Email from "~/components/Stations/Webhooks/Form/Email.vue";
import Tunein from "~/components/Stations/Webhooks/Form/Tunein.vue";
import Discord from "~/components/Stations/Webhooks/Form/Discord.vue";
import Telegram from "~/components/Stations/Webhooks/Form/Telegram.vue";
import GoogleAnalyticsV4 from "~/components/Stations/Webhooks/Form/GoogleAnalyticsV4.vue";
import MatomoAnalytics from "~/components/Stations/Webhooks/Form/MatomoAnalytics.vue";
import Mastodon from "~/components/Stations/Webhooks/Form/Mastodon.vue";
import {BaseEditModalProps, HasRelistEmit, useBaseEditModal} from "~/functions/useBaseEditModal";
import type {Component} from "vue";
import {computed, nextTick, provide, ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import {WebhookTriggerDetails, WebhookType, WebhookTypeDetails, WebhookTypes} from "~/entities/Webhooks";
import Tabs from "~/components/Common/Tabs.vue";
import RadioDe from "~/components/Stations/Webhooks/Form/RadioDe.vue";
import GetMeRadio from "~/components/Stations/Webhooks/Form/GetMeRadio.vue";
import RadioReg from "~/components/Stations/Webhooks/Form/RadioReg.vue";
import GroupMe from "~/components/Stations/Webhooks/Form/GroupMe.vue";
import Bluesky from "~/components/Stations/Webhooks/Form/Bluesky.vue";
import mergeExisting from "~/functions/mergeExisting.ts";

export interface WebhookComponentProps {
    title: string
}

interface WebhookEditModalProps extends BaseEditModalProps {
    nowPlayingUrl: string,
    typeDetails: WebhookTypeDetails,
    triggerDetails: WebhookTriggerDetails
}

const props = defineProps<WebhookEditModalProps>();

provide('nowPlayingUrl', props.nowPlayingUrl);

const emit = defineEmits<HasRelistEmit>();

const type = ref<WebhookType | null>(null);

const $modal = useTemplateRef('$modal');

const webhookComponents: {
    [key in WebhookType]: Component
} = {
    [WebhookTypes.Generic]: Generic,
    [WebhookTypes.Email]: Email,
    [WebhookTypes.TuneIn]: Tunein,
    [WebhookTypes.RadioDe]: RadioDe,
    [WebhookTypes.RadioReg]: RadioReg,
    [WebhookTypes.GetMeRadio]: GetMeRadio,
    [WebhookTypes.Discord]: Discord,
    [WebhookTypes.Telegram]: Telegram,
    [WebhookTypes.GroupMe]: GroupMe,
    [WebhookTypes.Mastodon]: Mastodon,
    [WebhookTypes.Bluesky]: Bluesky,
    [WebhookTypes.GoogleAnalyticsV4]: GoogleAnalyticsV4,
    [WebhookTypes.MatomoAnalytics]: MatomoAnalytics,
};

const typeTitle = computed<string | null>(() => {
    if (type.value === null) {
        return null;
    }

    return get(props.typeDetails, [type.value as string, 'title'], '');
});

const formComponent = computed<Component>(() => {
    if (type.value === null) {
        return Generic;
    }

    return get(webhookComponents, type.value as string, Generic);
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
    {
        type: {}
    },
    {
        type: WebhookTypes.Generic as string
    },
    {
        populateForm: (data, formRef) => {
            type.value = data.type as WebhookType | null;

            // Wait for type-specific components to mount.
            void nextTick(() => {
                resetForm();
                formRef.value = mergeExisting(formRef.value, data);
            });
        },
        getSubmittableFormData(formRef, isEditModeRef) {
            const formData = formRef.value;
            if (!isEditModeRef.value) {
                formData.type = type.value as string | null;
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

const setType = (newType: WebhookType) => {
    type.value = newType;
    void nextTick(resetForm);
};

defineExpose({
    create,
    edit,
    close
});
</script>

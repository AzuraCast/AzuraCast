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

<script lang="ts">
import {FormTabProps} from "~/functions/useVuelidateOnFormTab.ts";

export interface WebhookComponentProps extends FormTabProps {
    title: string
}
</script>

<script setup lang="ts">
import TypeSelect from "./Form/TypeSelect.vue";
import BasicInfo from "./Form/BasicInfo.vue";
import {get} from "lodash";
import Generic from "./Form/Generic.vue";
import Email from "./Form/Email.vue";
import Tunein from "./Form/Tunein.vue";
import Discord from "./Form/Discord.vue";
import Telegram from "./Form/Telegram.vue";
import GoogleAnalyticsV4 from "./Form/GoogleAnalyticsV4.vue";
import MatomoAnalytics from "./Form/MatomoAnalytics.vue";
import Mastodon from "./Form/Mastodon.vue";
import {BaseEditModalProps, HasRelistEmit, ModalFormTemplateRef, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, nextTick, provide, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import {WebhookTriggerDetails, WebhookType, WebhookTypeDetails} from "~/entities/Webhooks";
import Tabs from "~/components/Common/Tabs.vue";
import RadioDe from "~/components/Stations/Webhooks/Form/RadioDe.vue";
import GetMeRadio from "~/components/Stations/Webhooks/Form/GetMeRadio.vue";
import RadioReg from "~/components/Stations/Webhooks/Form/RadioReg.vue";
import GroupMe from "~/components/Stations/Webhooks/Form/GroupMe.vue";
import Bluesky from "~/components/Stations/Webhooks/Form/Bluesky.vue";
import mergeExisting from "~/functions/mergeExisting.ts";

interface WebhookEditModalProps extends BaseEditModalProps {
    nowPlayingUrl: string,
    typeDetails: WebhookTypeDetails,
    triggerDetails: WebhookTriggerDetails
}

const props = defineProps<WebhookEditModalProps>();

provide('nowPlayingUrl', props.nowPlayingUrl);

const emit = defineEmits<HasRelistEmit>();

const type = ref<WebhookType | null>(null);

const $modal = ref<ModalFormTemplateRef>(null);

const webhookComponents = {
    [WebhookType.Generic]: Generic,
    [WebhookType.Email]: Email,
    [WebhookType.TuneIn]: Tunein,
    [WebhookType.RadioDe]: RadioDe,
    [WebhookType.RadioReg]: RadioReg,
    [WebhookType.GetMeRadio]: GetMeRadio,
    [WebhookType.Discord]: Discord,
    [WebhookType.Telegram]: Telegram,
    [WebhookType.GroupMe]: GroupMe,
    [WebhookType.Mastodon]: Mastodon,
    [WebhookType.Bluesky]: Bluesky,
    [WebhookType.GoogleAnalyticsV4]: GoogleAnalyticsV4,
    [WebhookType.MatomoAnalytics]: MatomoAnalytics,
};

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
    {
        type: {}
    },
    {
        type: null
    },
    {
        populateForm: (data, formRef) => {
            type.value = data.type;

            // Wait for type-specific components to mount.
            nextTick(() => {
                resetForm();
                formRef.value = mergeExisting(formRef.value, data);
            });
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

const setType = (newType: WebhookType) => {
    type.value = newType;
    nextTick(resetForm);
};

defineExpose({
    create,
    edit,
    close
});
</script>

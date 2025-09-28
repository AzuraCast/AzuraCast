<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="r$.$invalid"
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
                :trigger-details="triggerDetails"
            />

            <component
                :is="formComponent"
                :title="typeTitle"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import TypeSelect from "~/components/Stations/Webhooks/Form/TypeSelect.vue";
import BasicInfo from "~/components/Stations/Webhooks/Form/BasicInfo.vue";
import {get} from "es-toolkit/compat";
import Generic from "~/components/Stations/Webhooks/Form/Generic.vue";
import Email from "~/components/Stations/Webhooks/Form/Email.vue";
import Tunein from "~/components/Stations/Webhooks/Form/Tunein.vue";
import Discord from "~/components/Stations/Webhooks/Form/Discord.vue";
import Telegram from "~/components/Stations/Webhooks/Form/Telegram.vue";
import GoogleAnalyticsV4 from "~/components/Stations/Webhooks/Form/GoogleAnalyticsV4.vue";
import MatomoAnalytics from "~/components/Stations/Webhooks/Form/MatomoAnalytics.vue";
import Mastodon from "~/components/Stations/Webhooks/Form/Mastodon.vue";
import {BaseEditModalProps, HasRelistEmit, useBaseEditModal} from "~/functions/useBaseEditModal";
import {type Component, computed, provide, toRef, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import {
    ActiveWebhookTypes,
    WebhookRecord,
    WebhookResponseBody,
    WebhookTriggerDetails,
    WebhookTypeDetails
} from "~/entities/Webhooks";
import Tabs from "~/components/Common/Tabs.vue";
import RadioDe from "~/components/Stations/Webhooks/Form/RadioDe.vue";
import GetMeRadio from "~/components/Stations/Webhooks/Form/GetMeRadio.vue";
import RadioReg from "~/components/Stations/Webhooks/Form/RadioReg.vue";
import GroupMe from "~/components/Stations/Webhooks/Form/GroupMe.vue";
import Bluesky from "~/components/Stations/Webhooks/Form/Bluesky.vue";
import {WebhookTypes} from "~/entities/ApiInterfaces.ts";
import mergeExisting from "~/functions/mergeExisting.ts";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {useAppCollectScope} from "~/vendor/regle.ts";
import {storeToRefs} from "pinia";

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

const $modal = useTemplateRef('$modal');

const formStore = useStationsWebhooksForm();
const {form, type} = storeToRefs(formStore);
const {$reset: resetForm, setType} = formStore;

const {r$} = useAppCollectScope('station-webhooks');

const webhookComponents: {
    [key in ActiveWebhookTypes]?: Component
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

    return get(props.typeDetails, [type.value, 'title'], '');
});

const formComponent = computed<Component>(() => {
    if (type.value === null) {
        return Generic;
    }

    return get(webhookComponents, type.value, Generic);
});


const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal<
    WebhookRecord,
    WebhookResponseBody
>(
    toRef(props, 'createUrl'),
    emit,
    $modal,
    () => {
        resetForm();
        r$.$reset();
    },
    (data) => {
        if (data.type) {
            setType(data.type);
        }

        form.value = mergeExisting(form.value, data);
        r$.$reset();
    },
    async () => {
        const {valid} = await r$.$validate();
        return {valid, data: form.value};
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

defineExpose({
    create,
    edit,
    close
});
</script>

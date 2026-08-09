<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_webhookurl"
                class="col-md-12"
                :field="r$.config.webhookurl"
                :label="$gettext('RadioReg Webhook URL')"
                :description="$gettext('Found under the settings page for the corresponding RadioReg station.')"
            />

            <form-group-field
                id="form_config_apikey"
                class="col-md-6"
                :field="r$.config.apikey"
                :label="$gettext('RadioReg Organization API Key')"
                :description="$gettext('An API token is issued on a per-organization basis and are found on the org. settings page.')"
            />

            <form-group-checkbox
                id="form_config_send_cover_art"
                class="col-md-12"
                :field="r$.config.send_cover_art"
                :label="$gettext('Send Cover Art')"
                :description="$gettext('If enabled, the cover art URLs for the current and next song will be included in the webhook payload. If disabled, RadioReg will look up cover art on its own.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import { required } from "@regle/rules";
import { storeToRefs } from "pinia";
import { Ref } from "vue";
import Tab from "~/components/Common/Tab.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import { WebhookComponentProps } from "~/components/Stations/Webhooks/EditModal.vue";
import { useStationsWebhooksForm } from "~/components/Stations/Webhooks/Form/form.ts";
import {
    WebhookRecordCommon,
    WebhookRecordRadioReg,
} from "~/entities/Webhooks.ts";
import { useFormTabClass } from "~/functions/useFormTabClass.ts";
import { useAppScopedRegle } from "~/vendor/regle.ts";

defineProps<WebhookComponentProps>();

const { form } = storeToRefs(useStationsWebhooksForm());

const { r$ } = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordRadioReg>,
    {
        config: {
            webhookurl: { required },
            apikey: { required },
        },
    },
    {
        namespace: "station-webhooks",
    },
);

const tabClass = useFormTabClass(r$);
</script>

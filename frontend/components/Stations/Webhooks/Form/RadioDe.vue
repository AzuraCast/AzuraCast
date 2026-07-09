<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_broadcastsubdomain"
                class="col-md-12"
                :field="r$.config.broadcastsubdomain"
                :label="$gettext('Radio.de Broadcast Subdomain')"
            />

            <form-group-field
                id="form_config_apikey"
                class="col-md-6"
                :field="r$.config.apikey"
                :label="$gettext('Radio.de API Key')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import { required } from "@regle/rules";
import { storeToRefs } from "pinia";
import { Ref } from "vue";
import Tab from "~/components/Common/Tab.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import { WebhookComponentProps } from "~/components/Stations/Webhooks/EditModal.vue";
import { useStationsWebhooksForm } from "~/components/Stations/Webhooks/Form/form.ts";
import {
    WebhookRecordCommon,
    WebhookRecordRadioDe,
} from "~/entities/Webhooks.ts";
import { useFormTabClass } from "~/functions/useFormTabClass.ts";
import { useAppScopedRegle } from "~/vendor/regle.ts";

defineProps<WebhookComponentProps>();

const { form } = storeToRefs(useStationsWebhooksForm());

const { r$ } = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordRadioDe>,
    {
        config: {
            broadcastsubdomain: { required },
            apikey: { required },
        },
    },
    {
        namespace: "station-webhooks",
    },
);

const tabClass = useFormTabClass(r$);
</script>

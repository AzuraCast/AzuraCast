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
import FormGroupField from "~/components/Form/FormGroupField.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {WebhookRecordCommon, WebhookRecordRadioDe} from "~/components/Stations/Webhooks/Form/form.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";

defineProps<WebhookComponentProps>();

type ThisWebhookRecord = WebhookRecordCommon & WebhookRecordRadioDe;

const form = defineModel<ThisWebhookRecord>('form', {required: true});

const {r$} = useAppScopedRegle(
    form,
    {
        config: {
            broadcastsubdomain: {required},
            apikey: {required}
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);
</script>

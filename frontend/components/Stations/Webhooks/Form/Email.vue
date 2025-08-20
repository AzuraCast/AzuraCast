<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_to"
                class="col-md-12"
                :field="r$.config.to"
                :label="$gettext('Message Recipient(s)')"
                :description="$gettext('E-mail addresses can be separated by commas.')"
            />
        </div>

        <common-formatting-info />

        <div class="row g-3">
            <form-group-field
                id="form_config_subject"
                class="col-md-12"
                :field="r$.config.subject"
                :label="$gettext('Message Subject')"
            />

            <form-group-field
                id="form_config_message"
                class="col-md-12"
                :field="r$.config.message"
                :label="$gettext('Message Body')"
                input-type="textarea"
                :input-attrs="{rows: 4}"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "~/components/Stations/Webhooks/Form/Common/FormattingInfo.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {WebhookRecordCommon, WebhookRecordEmail} from "~/components/Stations/Webhooks/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";

defineProps<WebhookComponentProps>();

type ThisWebhookRecord = WebhookRecordCommon & WebhookRecordEmail;

const form = defineModel<ThisWebhookRecord>('form', {required: true});

const {r$} = useAppScopedRegle(
    form,
    {
        config: {
            to: {required},
            subject: {required},
            message: {required}
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);
</script>

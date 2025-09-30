<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_bot_id"
                class="col-md-6"
                :field="r$.config.bot_id"
                :label="$gettext('Bot ID')"
            >
                <template #description>
                    <a
                        href="https://dev.groupme.com/tutorials/bots"
                        target="_blank"
                    >
                        {{ $gettext('See the GroupMe Documentation for more details.') }}
                    </a>
                </template>
            </form-group-field>

            <form-group-field
                id="form_config_api"
                class="col-md-6"
                :field="r$.config.api"
                :label="$gettext('Custom API Base URL')"
                :description="$gettext('Leave blank to use the default GroupMe API URL (recommended).')"
            />
        </div>

        <common-formatting-info />

        <div class="row g-3">
            <form-group-field
                id="form_config_text"
                class="col-md-12"
                :field="r$.config.text"
                input-type="textarea"
                :label="$gettext('Main Message Content')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "~/components/Stations/Webhooks/Form/Common/FormattingInfo.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {storeToRefs} from "pinia";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {Ref} from "vue";
import {WebhookRecordCommon, WebhookRecordGroupMe} from "~/entities/Webhooks.ts";

defineProps<WebhookComponentProps>();

const {form} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordGroupMe>,
    {
        config: {
            bot_id: {required},
            text: {required}
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);
</script>

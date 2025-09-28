<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_bot_token"
                class="col-md-6"
                :field="r$.config.bot_token"
                :label="$gettext('Bot Token')"
            >
                <template #description>
                    <a
                        href="https://core.telegram.org/bots#botfather"
                        target="_blank"
                    >
                        {{ $gettext('See the Telegram Documentation for more details.') }}
                    </a>
                </template>
            </form-group-field>

            <form-group-field
                id="form_config_chat_id"
                class="col-md-6"
                :field="r$.config.chat_id"
                :label="$gettext('Chat ID')"
                :description="$gettext('Unique identifier for the target chat or username of the target channel (in the format @channelusername).')"
            />

            <form-group-field
                id="form_config_api"
                class="col-md-6"
                :field="r$.config.api"
                :label="$gettext('Custom API Base URL')"
                :description="$gettext('Leave blank to use the default Telegram API URL (recommended).')"
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

            <form-group-multi-check
                id="form_config_parse_mode"
                class="col-md-12"
                :field="r$.config.parse_mode"
                :options="parseModeOptions"
                stacked
                radio
                :label="$gettext('Message parsing mode')"
            >
                <template #description>
                    <a
                        href="https://core.telegram.org/bots/api#sendmessage"
                        target="_blank"
                    >
                        {{ $gettext('See the Telegram documentation for more details.') }}
                    </a>
                </template>
            </form-group-multi-check>
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "~/components/Stations/Webhooks/Form/Common/FormattingInfo.vue";
import {computed, Ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {storeToRefs} from "pinia";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {WebhookRecordCommon, WebhookRecordTelegram} from "~/entities/Webhooks.ts";

defineProps<WebhookComponentProps>();

const {form} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordTelegram>,
    {
        config: {
            bot_token: {required},
            chat_id: {required},
            text: {required},
            parse_mode: {required}
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);

const {$gettext} = useTranslate();

const parseModeOptions = computed(() => {
    return [
        {
            text: $gettext('Markdown'),
            value: 'Markdown',
        },
        {
            text: $gettext('HTML'),
            value: 'HTML',
        }
    ];
});
</script>

<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_webhook_url"
                class="col-md-12"
                :field="r$.config.webhook_url"
                input-type="url"
                :label="$gettext('Discord Web Hook URL')"
                :description="$gettext('This URL is provided within the Discord application.')"
            />
        </div>

        <common-formatting-info />

        <div class="row g-3">
            <form-group-field
                id="form_config_content"
                class="col-md-6"
                :field="r$.config.content"
                input-type="textarea"
                :label="$gettext('Main Message Content')"
            />

            <form-group-field
                id="form_config_title"
                class="col-md-6"
                :field="r$.config.title"
                :label="$gettext('Title')"
            />

            <form-group-field
                id="form_config_description"
                class="col-md-6"
                :field="r$.config.description"
                input-type="textarea"
                :label="$gettext('Description')"
            />

            <form-group-field
                id="form_config_url"
                class="col-md-6"
                :field="r$.config.url"
                input-type="url"
                :label="$gettext('URL')"
            />

            <form-group-field
                id="form_config_author"
                class="col-md-6"
                :field="r$.config.author"
                :label="$gettext('Author')"
            />

            <form-group-field
                id="form_config_thumbnail"
                class="col-md-6"
                :field="r$.config.thumbnail"
                input-type="url"
                :label="$gettext('Thumbnail Image URL')"
            />

            <form-group-field
                id="form_config_footer"
                class="col-md-6"
                :field="r$.config.footer"
                :label="$gettext('Footer Text')"
            />

            <form-group-field
                id="form_config_color" 
                class="col-md-6"
                :field="r$.config.color"
                :label="$gettext('Embed Color (Hex)')"
            />

            <form-group-checkbox
                id="form_config_include_timestamp"
                class="col-md-12"
                :field="r$.config.include_timestamp"
                :label="$gettext('Include Timestamp')"
                :description="$gettext('If set, the time sent will be included in the embed footer.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "~/components/Stations/Webhooks/Form/Common/FormattingInfo.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {isValidHexColor, useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {storeToRefs} from "pinia";
import {Ref} from "vue";
import {WebhookRecordCommon, WebhookRecordDiscord} from "~/entities/Webhooks.ts";

defineProps<WebhookComponentProps>();

const {form} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordDiscord>,
    {
        config: {
            webhook_url: {required},
            color: {isValidHexColor},
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);
</script>

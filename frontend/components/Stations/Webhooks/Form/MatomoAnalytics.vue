<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_matomo_url"
                class="col-md-12"
                :field="r$.config.matomo_url"
                input-type="url"
                :label="$gettext('Matomo Installation Base URL')"
                :description="$gettext('The full base URL of your Matomo installation.')"
            />

            <form-group-field
                id="form_config_site_id"
                class="col-md-6"
                :field="r$.config.site_id"
                :label="$gettext('Matomo Site ID')"
                :description="$gettext('The numeric site ID for this site.')"
            />

            <form-group-field
                id="form_config_token"
                class="col-md-6"
                :field="r$.config.token"
                :label="$gettext('Matomo API Token')"
                :description="$gettext('Optionally supply an API token to allow IP address overriding.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {storeToRefs} from "pinia";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {Ref} from "vue";
import {WebhookRecordCommon, WebhookRecordMatomoAnalytics} from "~/entities/Webhooks.ts";

defineProps<WebhookComponentProps>();

const {form} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordMatomoAnalytics>,
    {
        config: {
            matomo_url: {required},
            site_id: {required},
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);
</script>

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
import {storeToRefs} from "pinia";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {computed} from "vue";

defineProps<WebhookComponentProps>();

const {r$} = storeToRefs(useStationsWebhooksForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.matomoAnalyticsWebhookTab));
</script>

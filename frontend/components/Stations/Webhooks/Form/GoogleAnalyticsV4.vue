<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_api_secret"
                class="col-md-6"
                :field="r$.config.api_secret"
                :label="$gettext('Measurement Protocol API Secret')"
                :description="$gettext('This can be generated in the &quot;Events&quot; section for a measurement.')"
            />

            <form-group-field
                id="form_config_measurement_id"
                class="col-md-6"
                :field="r$.config.measurement_id"
                :label="$gettext('Measurement ID')"
                :description="$gettext('A unique identifier (i.e. &quot;G-A1B2C3D4&quot;) for this measurement stream.')"
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

const tabClass = useFormTabClass(computed(() => r$.value.$groups.googleAnalyticsV4WebhookTab));
</script>

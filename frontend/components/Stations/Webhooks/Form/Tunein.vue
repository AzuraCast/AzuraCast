<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_station_id"
                class="col-md-6"
                :field="r$.config.station_id"
                :label="$gettext('TuneIn Station ID')"
                :description="$gettext('The station ID will be a numeric string that starts with the letter S.')"
            />

            <form-group-field
                id="form_config_partner_id"
                class="col-md-6"
                :field="r$.config.partner_id"
                :label="$gettext('TuneIn Partner ID')"
            />

            <form-group-field
                id="form_config_partner_key"
                class="col-md-6"
                :field="r$.config.partner_key"
                :label="$gettext('TuneIn Partner Key')"
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
import {WebhookRecordCommon, WebhookRecordTuneIn} from "~/entities/Webhooks.ts";

defineProps<WebhookComponentProps>();

const {form} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordTuneIn>,
    {
        config: {
            station_id: {required},
            partner_id: {required},
            partner_key: {required},
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);
</script>

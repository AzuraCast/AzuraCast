<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_token"
                class="col-md-6"
                :field="r$.config.token"
                :label="$gettext('API Token')"
                :description="$gettext('This can be retrieved from the GetMeRadio dashboard.')"
            />

            <form-group-field
                id="form_config_station_id"
                class="col-md-6"
                :field="r$.config.station_id"
                :label="$gettext('GetMeRadio Station ID')"
                :description="$gettext('This is a 3-5 digit number.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {storeToRefs} from "pinia";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {Ref} from "vue";
import {WebhookRecordCommon, WebhookRecordGetMeRadio} from "~/entities/Webhooks.ts";

defineProps<WebhookComponentProps>();

const {form} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordGetMeRadio>,
    {
        config: {
            token: {required},
            station_id: {required}
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);
</script>

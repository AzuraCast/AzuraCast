<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_station_id"
                class="col-md-6"
                :field="v$.config.station_id"
                :label="$gettext('TuneIn Station ID')"
                :description="$gettext('The station ID will be a numeric string that starts with the letter S.')"
            />

            <form-group-field
                id="form_config_partner_id"
                class="col-md-6"
                :field="v$.config.partner_id"
                :label="$gettext('TuneIn Partner ID')"
            />

            <form-group-field
                id="form_config_partner_key"
                class="col-md-6"
                :field="v$.config.partner_key"
                :label="$gettext('TuneIn Partner Key')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {FormTabEmits, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";

const props = defineProps<WebhookComponentProps>();
const emit = defineEmits<FormTabEmits>();

const {v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    {
        config: {
            station_id: {required},
            partner_id: {required},
            partner_key: {required},
        }
    },
    () => ({
        config: {
            station_id: '',
            partner_id: '',
            partner_key: ''
        }
    })
);
</script>

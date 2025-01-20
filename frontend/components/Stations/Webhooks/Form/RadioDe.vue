<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_broadcastsubdomain"
                class="col-md-12"
                :field="v$.config.broadcastsubdomain"
                :label="$gettext('Radio.de Broadcast Subdomain')"
            />

            <form-group-field
                id="form_config_apikey"
                class="col-md-6"
                :field="v$.config.apikey"
                :label="$gettext('Radio.de API Key')"
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
            broadcastsubdomain: {required},
            apikey: {required}
        }
    },
    () => ({
        config: {
            broadcastsubdomain: '',
            apikey: ''
        }
    })
);
</script>

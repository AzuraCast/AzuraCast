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
import {useValidatedFormTab} from "~/functions/useValidatedFormTab.ts";
import {required} from "@regle/rules";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {ApiGenericForm} from "~/entities/ApiInterfaces.ts";

defineProps<WebhookComponentProps>();

const form = defineModel<ApiGenericForm>('form', {required: true});

const {v$, tabClass} = useValidatedFormTab(
    form,
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

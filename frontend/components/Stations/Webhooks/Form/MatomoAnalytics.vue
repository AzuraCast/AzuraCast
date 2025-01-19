<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_matomo_url"
                class="col-md-12"
                :field="v$.config.matomo_url"
                input-type="url"
                :label="$gettext('Matomo Installation Base URL')"
                :description="$gettext('The full base URL of your Matomo installation.')"
            />

            <form-group-field
                id="form_config_site_id"
                class="col-md-6"
                :field="v$.config.site_id"
                :label="$gettext('Matomo Site ID')"
                :description="$gettext('The numeric site ID for this site.')"
            />

            <form-group-field
                id="form_config_token"
                class="col-md-6"
                :field="v$.config.token"
                :label="$gettext('Matomo API Token')"
                :description="$gettext('Optionally supply an API token to allow IP address overriding.')"
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
            matomo_url: {required},
            site_id: {required},
            token: {},
        }
    },
    () => ({
        config: {
            matomo_url: '',
            site_id: '',
            token: ''
        }
    })
);
</script>

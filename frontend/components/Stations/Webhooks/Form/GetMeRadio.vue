<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_config_token"
                class="col-md-6"
                :field="v$.config.token"
                :label="$gettext('API Token')"
                :description="$gettext('This can be retrieved from the GetMeRadio dashboard.')"
            />

            <form-group-field
                id="form_config_station_id"
                class="col-md-6"
                :field="v$.config.station_id"
                :label="$gettext('GetMeRadio Station ID')"
                :description="$gettext('This is a 3-5 digit number.')"
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
            token: {required},
            station_id: {required}
        }
    },
    () => ({
        config: {
            token: '',
            station_id: '',
        }
    })
);
</script>

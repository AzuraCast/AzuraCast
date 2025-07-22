<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_to"
                class="col-md-12"
                :field="v$.config.to"
                :label="$gettext('Message Recipient(s)')"
                :description="$gettext('E-mail addresses can be separated by commas.')"
            />
        </div>

        <common-formatting-info />

        <div class="row g-3">
            <form-group-field
                id="form_config_subject"
                class="col-md-12"
                :field="v$.config.subject"
                :label="$gettext('Message Subject')"
            />

            <form-group-field
                id="form_config_message"
                class="col-md-12"
                :field="v$.config.message"
                :label="$gettext('Message Body')"
                input-type="textarea"
                :input-attrs="{rows: 4}"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "~/components/Stations/Webhooks/Form/Common/FormattingInfo.vue";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {ApiGenericForm} from "~/entities/ApiInterfaces.ts";

defineProps<WebhookComponentProps>();

const form = defineModel<ApiGenericForm>('form', {required: true});

const {v$, tabClass} = useVuelidateOnFormTab(
    form,
    {
        config: {
            to: {required},
            subject: {required},
            message: {required}
        }
    },
    () => ({
        config: {
            to: '',
            subject: '',
            message: ''
        }
    })
);
</script>

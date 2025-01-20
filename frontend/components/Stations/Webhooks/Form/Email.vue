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
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "./Common/FormattingInfo.vue";
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

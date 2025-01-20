<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_username"
                class="col-md-6"
                :field="v$.config.handle"
                :label="$gettext('Bluesky Handle')"
                :description="$gettext('The username associated with your account.')"
            />

            <form-group-field
                id="form_config_password"
                class="col-md-6"
                :field="v$.config.app_password"
                :label="$gettext('App Password')"
                :description="$gettext('Create a new App Password for this service, then enter the key here (i.e. 0123-abcd-4567)')"
            />
        </div>

        <common-social-post-fields
            v-model:form="form"
        />
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonSocialPostFields from "./Common/SocialPostFields.vue";
import {FormTabEmits, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";

const props = defineProps<WebhookComponentProps>();
const emit = defineEmits<FormTabEmits>();

const {form, v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    {
        config: {
            handle: {required},
            app_password: {required}
        }
    },
    () => ({
        config: {
            handle: '',
            app_password: ''
        }
    })
);
</script>

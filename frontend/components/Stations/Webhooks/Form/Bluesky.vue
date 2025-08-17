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
import CommonSocialPostFields from "~/components/Stations/Webhooks/Form/Common/SocialPostFields.vue";
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

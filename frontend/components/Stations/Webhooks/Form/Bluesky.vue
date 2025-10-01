<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_username"
                class="col-md-6"
                :field="r$.config.handle"
                :label="$gettext('Bluesky Handle')"
                :description="$gettext('The username associated with your account.')"
            />

            <form-group-field
                id="form_config_password"
                class="col-md-6"
                :field="r$.config.app_password"
                :label="$gettext('App Password')"
                :description="$gettext('Create a new App Password for this service, then enter the key here (i.e. 0123-abcd-4567)')"
            />
        </div>

        <common-social-post-fields/>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonSocialPostFields from "~/components/Stations/Webhooks/Form/Common/SocialPostFields.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {storeToRefs} from "pinia";
import {WebhookRecordBluesky, WebhookRecordCommon} from "~/entities/Webhooks.ts";
import {Ref} from "vue";

defineProps<WebhookComponentProps>();

const {form} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordBluesky>,
    {
        config: {
            handle: {required},
            app_password: {required}
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);
</script>

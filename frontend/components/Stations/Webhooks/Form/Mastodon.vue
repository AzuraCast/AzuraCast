<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <form-markup id="mastodon_details">
            <template #label>
                {{ $gettext('Mastodon Account Details') }}
            </template>

            <p class="card-text">
                {{ $gettext('Steps for configuring a Mastodon application:') }}
            </p>
            <ul>
                <li>
                    {{ $gettext('Visit your Mastodon instance.') }}
                </li>
                <li>
                    {{ $gettext('Click the "Preferences" link, then "Development" on the left side menu.') }}
                </li>
                <li>
                    {{ $gettext('Click "New Application"') }}
                </li>
                <li>
                    {{
                        $gettext('Enter "AzuraCast" as the application name. You can leave the URL fields unchanged. For "Scopes", only "write:media" and "write:statuses" are required.')
                    }}
                </li>
            </ul>
            <p class="card-text">
                {{
                    $gettext('Once these steps are completed, enter the "Access Token" from the application\'s page into the field below.')
                }}
            </p>
        </form-markup>

        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_instance_url"
                class="col-md-6"
                :field="r$.config.instance_url"
                :label="$gettext('Mastodon Instance URL')"
                :description="$gettext('If your Mastodon username is &quot;@test@example.com&quot;, enter &quot;example.com&quot;.')"
            />

            <form-group-field
                id="form_config_access_token"
                class="col-md-6"
                :field="r$.config.access_token"
                :label="$gettext('Access Token')"
            />
        </div>

        <div class="row g-3 mb-3">
            <form-group-multi-check
                id="form_config_visibility"
                class="col-md-12"
                :field="r$.config.visibility"
                :options="visibilityOptions"
                stacked
                radio
                :label="$gettext('Message Visibility')"
            />
        </div>

        <common-social-post-fields/>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonSocialPostFields from "~/components/Stations/Webhooks/Form/Common/SocialPostFields.vue";
import {computed, Ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {useStationsWebhooksForm,} from "~/components/Stations/Webhooks/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {storeToRefs} from "pinia";
import {WebhookRecordCommon, WebhookRecordMastodon} from "~/entities/Webhooks.ts";

defineProps<WebhookComponentProps>();

const {form} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form as Ref<WebhookRecordCommon & WebhookRecordMastodon>,
    {
        config: {
            instance_url: {required},
            access_token: {required},
            visibility: {required}
        }
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);

const {$gettext} = useTranslate();

const visibilityOptions = computed(() => {
    return [
        {
            text: $gettext('Public'),
            value: 'public',
        },
        {
            text: $gettext('Unlisted'),
            value: 'unlisted',
        },
        {
            text: $gettext('Followers Only'),
            value: 'private',
        }
    ];
});
</script>

<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <form-markup id="twitter_account_details">
            <template #label>
                {{ $gettext('Twitter Account Details') }}
            </template>

            <p class="card-text">
                {{ $gettext('Steps for configuring a Twitter application:') }}
            </p>
            <ul>
                <li>
                    {{
                        $gettext('Create a new app on the Twitter Applications site. Use this installation\'s base URL as the application URL.')
                    }}
                    <br>
                    <a
                        href="https://developer.twitter.com/en/apps"
                        target="_blank"
                    >
                        {{ $gettext('Twitter Applications') }}
                    </a>
                </li>
                <li>
                    {{ $gettext('In the newly created application, click the "Keys and Access Tokens" tab.') }}
                </li>
                <li>
                    {{ $gettext('At the bottom of the page, click "Create my access token".') }}
                </li>
            </ul>
            <p class="card-text">
                {{
                    $gettext('Once these steps are completed, enter the information from the "Keys and Access Tokens" page into the fields below.')
                }}
            </p>
        </form-markup>

        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_consumer_key"
                class="col-md-6"
                :field="v$.config.consumer_key"
                :label="$gettext('Consumer Key (API Key)')"
            />

            <form-group-field
                id="form_config_consumer_secret"
                class="col-md-6"
                :field="v$.config.consumer_secret"
                :label="$gettext('Consumer Secret (API Secret)')"
            />

            <form-group-field
                id="form_config_token"
                class="col-md-6"
                :field="v$.config.token"
                :label="$gettext('Access Token')"
            />

            <form-group-field
                id="form_config_token_secret"
                class="col-md-6"
                :field="v$.config.token_secret"
                :label="$gettext('Access Token Secret')"
            />

            <common-rate-limit-fields v-model:form="form" />
        </div>

        <common-social-post-fields v-model:form="form" />
    </tab>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import CommonRateLimitFields from "./Common/RateLimitFields";
import CommonSocialPostFields from "./Common/SocialPostFields";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        config: {
            consumer_key: {required},
            consumer_secret: {required},
            token: {required},
            token_secret: {required},
        }
    },
    form,
    {
        config: {
            consumer_key: '',
            consumer_secret: '',
            token: '',
            token_secret: '',
        }
    }
);
</script>

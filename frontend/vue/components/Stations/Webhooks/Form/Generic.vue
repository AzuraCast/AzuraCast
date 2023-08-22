<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <form-markup id="webhook_details">
            <template #label>
                {{ $gettext('Web Hook Details') }}
            </template>

            <p class="card-text">
                {{
                    $gettext('Web hooks automatically send a HTTP POST request to the URL you specify to notify it any time one of the triggers you specify occurs on your station.')
                }}
            </p>
            <p class="card-text">
                {{
                    $gettext('The body of the POST message is the exact same as the NowPlaying API response for your station.')
                }}
            </p>
            <ul>
                <li>
                    <a
                        href="https://azuracast.com/api"
                        target="_blank"
                    >
                        {{ $gettext('NowPlaying API Response') }}
                    </a>
                </li>
            </ul>
            <p class="card-text">
                {{
                    $gettext('In order to process quickly, web hooks have a short timeout, so the responding service should be optimized to handle the request in under 2 seconds.')
                }}
            </p>
        </form-markup>

        <div class="row g-3">
            <form-group-field
                id="form_config_webhook_url"
                class="col-md-12"
                :field="v$.config.webhook_url"
                input-type="url"
                :label="$gettext('Web Hook URL')"
                :description="$gettext('The URL that will receive the POST messages any time an event is triggered.')"
            />

            <form-group-field
                id="form_config_basic_auth_username"
                class="col-md-6"
                :field="v$.config.basic_auth_username"
                :label="$gettext('Optional: HTTP Basic Authentication Username')"
                :description="$gettext('If your web hook requires HTTP basic authentication, provide the username here.')"
            />

            <form-group-field
                id="form_config_basic_auth_password"
                class="col-md-6"
                :field="v$.config.basic_auth_password"
                :label="$gettext('Optional: HTTP Basic Authentication Password')"
                :description="$gettext('If your web hook requires HTTP basic authentication, provide the password here.')"
            />

            <form-group-field
                id="form_config_timeout"
                class="col-md-6"
                :field="v$.config.timeout"
                input-type="number"
                :input-attrs="{ min: '0.0', max: '600.0', step: '0.1' }"
                :label="$gettext('Optional: Request Timeout (Seconds)')"
                :description="$gettext('The number of seconds to wait for a response from the remote server before cancelling the request.')"
            />
        </div>
    </tab>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
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
            webhook_url: {required},
            basic_auth_username: {},
            basic_auth_password: {},
            timeout: {},
        }
    },
    form,
    {
        config: {
            webhook_url: '',
            basic_auth_username: '',
            basic_auth_password: '',
            timeout: '5',
        }
    }
);
</script>

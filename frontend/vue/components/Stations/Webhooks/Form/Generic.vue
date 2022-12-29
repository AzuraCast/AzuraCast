<template>
    <b-form-group>
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
    </b-form-group>

    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_webhook_url"
                class="col-md-12"
                :field="form.config.webhook_url"
                input-type="url"
            >
                <template #label>
                    {{ $gettext('Web Hook URL') }}
                </template>
                <template #description>
                    {{ $gettext('The URL that will receive the POST messages any time an event is triggered.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_basic_auth_username"
                class="col-md-6"
                :field="form.config.basic_auth_username"
            >
                <template #label>
                    {{ $gettext('Optional: HTTP Basic Authentication Username') }}
                </template>
                <template #description>
                    {{ $gettext('If your web hook requires HTTP basic authentication, provide the username here.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_basic_auth_password"
                class="col-md-6"
                :field="form.config.basic_auth_password"
            >
                <template #label>
                    {{ $gettext('Optional: HTTP Basic Authentication Password') }}
                </template>
                <template #description>
                    {{ $gettext('If your web hook requires HTTP basic authentication, provide the password here.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_timeout"
                class="col-md-6"
                :field="form.config.timeout"
                input-type="number"
                :input-attrs="{ min: '0.0', max: '600.0', step: '0.1' }"
            >
                <template #label>
                    {{ $gettext('Optional: Request Timeout (Seconds)') }}
                </template>
                <template #description>
                    {{
                        $gettext('The number of seconds to wait for a response from the remote server before cancelling the request.')
                    }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: Object
});
</script>

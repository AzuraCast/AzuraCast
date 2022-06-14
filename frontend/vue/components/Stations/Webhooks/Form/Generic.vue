<template>
    <div>
        <b-form-group>
            <template #label>
                <translate key="lang_customize_message_hdr">Web Hook Details</translate>
            </template>

            <p class="card-text">
                <translate key="lang_customize_message_desc_1">Web hooks automatically send a HTTP POST request to the URL you specify to notify it any time one of the triggers you specify occurs on your station.</translate>
            </p>
            <p class="card-text">
                <translate key="lang_customize_message_desc_2">The body of the POST message is the exact same as the NowPlaying API response for your station.</translate>
            </p>
            <ul>
                <li>
                    <a href="https://azuracast.com/api" target="_blank">
                        <translate key="lang_customize_response_link">NowPlaying API Response</translate>
                    </a>
                </li>
            </ul>
            <p class="card-text">
                <translate key="lang_customize_message_desc_3">In order to process quickly, web hooks have a short timeout, so the responding service should be optimized to handle the request in under 2 seconds.</translate>
            </p>
        </b-form-group>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_webhook_url" :field="form.config.webhook_url"
                                      input-type="url">
                    <template #label="{lang}">
                        <translate :key="lang">Web Hook URL</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">The URL that will receive the POST messages any time an event is triggered.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_basic_auth_username"
                                      :field="form.config.basic_auth_username">
                    <template #label="{lang}">
                        <translate :key="lang">Optional: HTTP Basic Authentication Username</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">If your web hook requires HTTP basic authentication, provide the username here.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_basic_auth_password"
                                      :field="form.config.basic_auth_password">
                    <template #label="{lang}">
                        <translate :key="lang">Optional: HTTP Basic Authentication Password</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">If your web hook requires HTTP basic authentication, provide the password here.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_timeout"
                                      :field="form.config.timeout" input-type="number"
                                      :input-attrs="{ min: '0.0', max: '600.0', step: '0.1' }">
                    <template #label="{lang}">
                        <translate :key="lang">Optional: Request Timeout (Seconds)</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">The number of seconds to wait for a response from the remote server before cancelling the request.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'Generic',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    }
}
</script>

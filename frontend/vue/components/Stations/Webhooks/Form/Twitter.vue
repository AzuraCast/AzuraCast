<template>
    <b-form-group>
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
                <a href="https://developer.twitter.com/en/apps" target="_blank">
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
    </b-form-group>

    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-6" id="form_config_consumer_key" :field="form.config.consumer_key">
                <template #label="{lang}">
                    {{ $gettext('Consumer Key (API Key)') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_consumer_secret"
                                  :field="form.config.consumer_secret">
                <template #label="{lang}">
                    {{ $gettext('Consumer Secret (API Secret)') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_token" :field="form.config.token">
                <template #label="{lang}">
                    {{ $gettext('Access Token') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_token_secret" :field="form.config.token_secret">
                <template #label="{lang}">
                    {{ $gettext('Access Token Secret') }}
                </template>
            </b-wrapped-form-group>

            <common-rate-limit-fields :form="form"></common-rate-limit-fields>
        </b-form-row>
    </b-form-group>

    <common-social-post-fields :form="form" :now-playing-url="nowPlayingUrl"></common-social-post-fields>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonRateLimitFields from "./Common/RateLimitFields";
import CommonSocialPostFields from "./Common/SocialPostFields";

export default {
    name: 'Twitter',
    components: {CommonSocialPostFields, CommonRateLimitFields, BWrappedFormGroup},
    props: {
        form: Object,
        nowPlayingUrl: String
    }
}
</script>

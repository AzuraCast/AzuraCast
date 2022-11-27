<template>
    <div>
        <b-form-group>
            <template #label>
                <translate key="lang_twitter_instructions_hdr">Twitter Account Details</translate>
            </template>

            <p class="card-text">
                <translate key="lang_twitter_instructions_1">Steps for configuring a Twitter application:</translate>
            </p>
            <ul>
                <li>
                    <translate key="lang_twitter_instructions_1">Create a new app on the Twitter Applications site. Use this installation's base URL as the application URL.</translate>
                    <br>
                    <a href="https://developer.twitter.com/en/apps" target="_blank">
                        <translate key="lang_twitter_instructions_url">Twitter Applications</translate>
                    </a>
                </li>
                <li>
                    <translate key="lang_twitter_instructions_2">In the newly created application, click the "Keys and Access Tokens" tab.</translate>
                </li>
                <li>
                    <translate key="lang_twitter_instructions_3">At the bottom of the page, click "Create my access token".</translate>
                </li>
            </ul>
            <p class="card-text">
                <translate key="lang_twitter_instructions_4">Once these steps are completed, enter the information from the "Keys and Access Tokens" page into the fields below.</translate>
            </p>
        </b-form-group>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="form_config_consumer_key" :field="form.config.consumer_key">
                    <template #label="{lang}">
                        <translate :key="lang">Consumer Key (API Key)</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_consumer_secret"
                                      :field="form.config.consumer_secret">
                    <template #label="{lang}">
                        <translate :key="lang">Consumer Secret (API Secret)</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_token" :field="form.config.token">
                    <template #label="{lang}">
                        <translate :key="lang">Access Token</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_token_secret" :field="form.config.token_secret">
                    <template #label="{lang}">
                        <translate :key="lang">Access Token Secret</translate>
                    </template>
                </b-wrapped-form-group>

                <common-rate-limit-fields :form="form"></common-rate-limit-fields>
            </b-form-row>
        </b-form-group>

        <common-social-post-fields :form="form" :now-playing-url="nowPlayingUrl"></common-social-post-fields>
    </div>
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

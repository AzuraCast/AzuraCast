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

                <b-wrapped-form-group class="col-md-12" id="form_config_rate_limit" :field="form.config.rate_limit">
                    <template #label="{lang}">
                        <translate :key="lang">Only Send One Tweet Every...</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" :options="rateLimitOptions"
                                            v-model="props.field.$model">
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>

        <common-formatting-info></common-formatting-info>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_message" :field="form.config.message"
                                      input-type="textarea">
                    <template #label="{lang}">
                        <translate :key="lang">Message Body</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonFormattingInfo from "./CommonFormattingInfo";

export default {
    name: 'Twitter',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        langSeconds() {
            return this.$gettext('%{ seconds } seconds');
        },
        langMinutes() {
            return this.$gettext('%{ minutes } minutes');
        },
        rateLimitOptions() {
            return [
                {
                    text: this.$gettext('No Limit'),
                    value: 0,
                },
                {
                    text: this.$gettextInterpolate(this.langSeconds, {seconds: 15}),
                    value: 15,
                },
                {
                    text: this.$gettextInterpolate(this.langSeconds, {seconds: 30}),
                    value: 30,
                },
                {
                    text: this.$gettextInterpolate(this.langSeconds, {seconds: 60}),
                    value: 60,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 2}),
                    value: 120,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 5}),
                    value: 300,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 10}),
                    value: 600,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 15}),
                    value: 900,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 30}),
                    value: 1800,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 60}),
                    value: 3600,
                }
            ];
        }
    }
}
</script>

<template>
    <div>
        <b-form-group>
            <template #label>
                <translate key="lang_mastodon_hdr">Mastodon Account Details</translate>
            </template>

            <p class="card-text">
                <translate key="lang_mastodon_instructions_1">Steps for configuring a Mastodon application:</translate>
            </p>
            <ul>
                <li>
                    <translate key="lang_mastodon_instructions_1">Visit your Mastodon instance.</translate>
                </li>
                <li>
                    <translate key="lang_mastodon_instructions_2">Click the "Preferences" link, then "Development" on the left side menu.</translate>
                </li>
                <li>
                    <translate key="lang_mastodon_instructions_3">Click "New Application"</translate>
                </li>
                <li>
                    <translate key="lang_mastodon_instructions_4">Enter "AzuraCast" as the application name. You can leave the URL fields unchanged. For "Scopes", only "write:media" and "write:statuses" are required.</translate>
                </li>
            </ul>
            <p class="card-text">
                <translate key="lang_twitter_instructions_5">Once these steps are completed, enter the "Access Token" from the application's page into the field below.</translate>
            </p>
        </b-form-group>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="form_config_instance_url" :field="form.config.instance_url">
                    <template #label="{lang}">
                        <translate :key="lang">Mastodon Instance URL</translate>
                    </template>
                    <template #description="{lang}">
                        <translate
                            :key="lang">If your Mastodon username is "@test@example.com", enter "example.com".</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_access_token"
                                      :field="form.config.access_token">
                    <template #label="{lang}">
                        <translate :key="lang">Access Token</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_config_rate_limit" :field="form.config.rate_limit">
                    <template #label="{lang}">
                        <translate :key="lang">Only Post Once Every...</translate>
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

                <b-wrapped-form-group class="col-md-12" id="form_config_visibility" :field="form.config.visibility">
                    <template #label="{lang}">
                        <translate :key="lang">Message Visibility</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" :options="visibilityOptions"
                                            v-model="props.field.$model">
                        </b-form-radio-group>
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
        },
        visibilityOptions() {
            return [
                {
                    text: this.$gettext('Public'),
                    value: 'public',
                },
                {
                    text: this.$gettext('Unlisted'),
                    value: 'unlisted',
                },
                {
                    text: this.$gettext('Private'),
                    value: 'private',
                }
            ];
        }
    }
}
</script>

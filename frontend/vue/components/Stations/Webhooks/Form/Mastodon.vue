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

                <common-rate-limit-fields :form="form"></common-rate-limit-fields>
            </b-form-row>
        </b-form-group>

        <b-form-group>
            <b-form-row>
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

        <common-social-post-fields :form="form" :now-playing-url="nowPlayingUrl"></common-social-post-fields>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import RateLimitFields from "./Common/RateLimitFields";
import CommonRateLimitFields from "./Common/RateLimitFields";
import CommonSocialPostFields from "./Common/SocialPostFields";

export default {
    name: 'Mastodon',
    components: {
        CommonRateLimitFields,
        CommonSocialPostFields,
        RateLimitFields,
        BWrappedFormGroup
    },
    props: {
        form: Object,
        nowPlayingUrl: String
    },
    computed: {
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

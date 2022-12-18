<template>
    <b-form-group>
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
    </b-form-group>

    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-6" id="form_config_instance_url" :field="form.config.instance_url">
                <template #label="{lang}">
                    {{ $gettext('Mastodon Instance URL') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('If your Mastodon username is "@test@example.com", enter "example.com".') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_access_token"
                                  :field="form.config.access_token">
                <template #label="{lang}">
                    {{ $gettext('Access Token') }}
                </template>
            </b-wrapped-form-group>

            <common-rate-limit-fields :form="form"></common-rate-limit-fields>
        </b-form-row>
    </b-form-group>

    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-12" id="form_config_visibility" :field="form.config.visibility">
                <template #label="{lang}">
                    {{ $gettext('Message Visibility') }}
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

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
        <div class="form-row">
            <b-wrapped-form-group class="col-md-6" id="form_config_instance_url" :field="form.config.instance_url">
                <template #label>
                    {{ $gettext('Mastodon Instance URL') }}
                </template>
                <template #description>
                    {{ $gettext('If your Mastodon username is "@test@example.com", enter "example.com".') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_access_token"
                                  :field="form.config.access_token">
                <template #label>
                    {{ $gettext('Access Token') }}
                </template>
            </b-wrapped-form-group>

            <common-rate-limit-fields :form="form"></common-rate-limit-fields>
        </div>
    </b-form-group>

    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="form_config_visibility" :field="form.config.visibility">
                <template #label>
                    {{ $gettext('Message Visibility') }}
                </template>
                <template #default="props">
                    <b-form-radio-group stacked :id="props.id" :options="visibilityOptions"
                                        v-model="props.field.$model">
                    </b-form-radio-group>
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>

    <common-social-post-fields :form="form" :now-playing-url="nowPlayingUrl"></common-social-post-fields>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonRateLimitFields from "./Common/RateLimitFields";
import CommonSocialPostFields from "./Common/SocialPostFields";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    form: Object,
    nowPlayingUrl: String
});

const {$gettext} = useTranslate();

const visibilityOptions = computed(() => {
    return [
        {
            text: $gettext('Public'),
            value: 'public',
        },
        {
            text: $gettext('Unlisted'),
            value: 'unlisted',
        },
        {
            text: $gettext('Private'),
            value: 'private',
        }
    ];
});
</script>

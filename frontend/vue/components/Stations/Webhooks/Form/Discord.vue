<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_webhook_url"
                class="col-md-12"
                :field="form.config.webhook_url"
                input-type="url"
            >
                <template #label>
                    {{ $gettext('Discord Web Hook URL') }}
                </template>
                <template #description>
                    {{ $gettext('This URL is provided within the Discord application.') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>

    <common-formatting-info :now-playing-url="nowPlayingUrl" />

    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_content"
                class="col-md-6"
                :field="form.config.content"
                input-type="textarea"
            >
                <template #label>
                    {{ $gettext('Main Message Content') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_title"
                class="col-md-6"
                :field="form.config.title"
            >
                <template #label>
                    {{ $gettext('Title') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_description"
                class="col-md-6"
                :field="form.config.description"
                input-type="textarea"
            >
                <template #label>
                    {{ $gettext('Description') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_url"
                class="col-md-6"
                :field="form.config.url"
                input-type="url"
            >
                <template #label>
                    {{ $gettext('URL') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_author"
                class="col-md-6"
                :field="form.config.author"
            >
                <template #label>
                    {{ $gettext('Author') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_thumbnail"
                class="col-md-6"
                :field="form.config.thumbnail"
                input-type="url"
            >
                <template #label>
                    {{ $gettext('Thumbnail Image URL') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_footer"
                class="col-md-6"
                :field="form.config.footer"
            >
                <template #label>
                    {{ $gettext('Footer Text') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonFormattingInfo from "./Common/FormattingInfo";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    nowPlayingUrl: {
        type: String,
        required: true
    }
});
</script>

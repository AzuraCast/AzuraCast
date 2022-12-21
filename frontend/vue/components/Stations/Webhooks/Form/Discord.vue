<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="form_config_webhook_url" :field="form.config.webhook_url"
                                  input-type="url">
                <template #label>
                    {{ $gettext('Discord Web Hook URL') }}
                </template>
                <template #description>
                    {{ $gettext('This URL is provided within the Discord application.') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>

    <common-formatting-info :now-playing-url="nowPlayingUrl"></common-formatting-info>

    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-6" id="form_config_content" :field="form.config.content">
                <template #label>
                    {{ $gettext('Main Message Content') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_title" :field="form.config.title">
                <template #label>
                    {{ $gettext('Title') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_description" :field="form.config.description">
                <template #label>
                    {{ $gettext('Description') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_url" :field="form.config.url" input-type="url">
                <template #label>
                    {{ $gettext('URL') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_author" :field="form.config.author">
                <template #label>
                    {{ $gettext('Author') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_thumbnail" :field="form.config.thumbnail"
                                  input-type="url">
                <template #label>
                    {{ $gettext('Thumbnail Image URL') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_footer" :field="form.config.footer">
                <template #label>
                    {{ $gettext('Footer Text') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonFormattingInfo from "./Common/FormattingInfo";

export default {
    name: 'Discord',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        form: Object,
        nowPlayingUrl: String
    }
}
</script>

<template>
    <div>
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_webhook_url" :field="form.config.webhook_url"
                                      input-type="url">
                    <template #label="{lang}">
                        {{ $gettext('Discord Web Hook URL') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('This URL is provided within the Discord application.') }}
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>

        <common-formatting-info :now-playing-url="nowPlayingUrl"></common-formatting-info>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="form_config_content" :field="form.config.content">
                    <template #label="{lang}">
                        {{ $gettext('Main Message Content') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_title" :field="form.config.title">
                    <template #label="{lang}">
                        {{ $gettext('Title') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_description" :field="form.config.description">
                    <template #label="{lang}">
                        {{ $gettext('Description') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_url" :field="form.config.url" input-type="url">
                    <template #label="{lang}">
                        {{ $gettext('URL') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_author" :field="form.config.author">
                    <template #label="{lang}">
                        {{ $gettext('Author') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_thumbnail" :field="form.config.thumbnail"
                                      input-type="url">
                    <template #label="{lang}">
                        {{ $gettext('Thumbnail Image URL') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_footer" :field="form.config.footer">
                    <template #label="{lang}">
                        {{ $gettext('Footer Text') }}
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>
    </div>
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
